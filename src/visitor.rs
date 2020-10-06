use crate::parser::{LangItemKind, Parser, SearchMode};
use crate::parsers::html::HTMLParser;
use crate::parsers::js::JSParser;
use std::io::{Cursor, Read, Seek, SeekFrom};

pub struct Visitor<T: Read + Seek> {
    levels: Vec<LangItemKind>,
    cursor: Cursor<T>,
}

impl<T: Read + Seek> Visitor<T> {
    pub fn new<K: Read + Seek>(cur: Cursor<K>) -> Visitor<K> {
        Visitor {
            levels: vec![],
            cursor: cur,
        }
    }

    pub fn last_level(&self) -> Option<&LangItemKind> {
        self.levels.last()
    }

    pub fn start_level(&mut self, item_kind: LangItemKind) {
        self.levels.push(item_kind);
    }

    pub fn end_level(&mut self) -> Option<LangItemKind> {
        self.levels.pop()
    }

    pub fn last_parser(&self) -> Option<&Box<dyn Parser>> {
        self.levels.iter().rev().find_map(|item| {
            if let LangItemKind::Parser(parser) = item {
                Some(parser)
            } else {
                None
            }
        })
    }

    pub fn go_back(&mut self, len: usize) {
        let position = self.cursor.position();

        let inner = self.cursor.get_mut();

        let next_pos = position + len as u64;
        inner.seek(SeekFrom::Start(next_pos)).unwrap();
        self.cursor.set_position(next_pos);
    }

    pub fn eat_next_chars(&mut self, len: usize) -> Option<String> {
        let mut buf = vec![0; len];
        let position = self.cursor.position();

        let inner = self.cursor.get_mut();

        let read = inner.read(&mut buf).ok()?;

        if read == 0 {
            return None;
        }

        self.cursor.set_position(position + len as u64);

        String::from_utf8(buf).ok()
    }
}

fn is_whitespace(txt: &str) -> bool {
    txt.trim().len() == 0
}

impl<T: Read + Seek> Iterator for Visitor<T> {
    type Item = String;

    fn next(&mut self) -> Option<String> {
        let mut buf = String::new();
        let mut prev_char = false;

        'outer: loop {
            let parser = self.last_parser()?;
            let last_level = self.last_level()?;

            match last_level {
                LangItemKind::String(str_type) if parser.search_mode().is_string() => {
                    let in_str_parsers = parser.in_str_parsers();
                    let full_str_parsers = parser.in_full_str_parsers();

                    match str_type.end().eat(&mut self.cursor) {
                        Some(_) => {
                            self.end_level();
                            if buf.len() > 0 {
                                return Some(buf);
                            }
                        }
                        None => {
                            for parser in in_str_parsers {
                                let matcher = parser.start().unwrap();

                                match matcher.eat(&mut self.cursor) {
                                    Some(_) => {
                                        self.start_level(LangItemKind::Parser(parser));

                                        if buf.len() > 0 {
                                            return Some(buf);
                                        }
                                    }
                                    None => {}
                                }
                            }

                            let next_char = self.eat_next_chars(1);

                            match next_char {
                                Some(chr) if prev_char || !is_whitespace(&chr) => {
                                    prev_char = true;
                                    buf.push_str(&chr)
                                }
                                Some(_) => {}
                                None => {
                                    return None;
                                }
                            };

                            for parser in full_str_parsers {
                                let matcher = parser.string_check().unwrap();

                                if matcher.is_match(&buf) {
                                    self.start_level(LangItemKind::Parser(parser));
                                    self.go_back(buf.len());
                                    prev_char = false;
                                    continue 'outer;
                                }
                            }
                        }
                    }
                }
                LangItemKind::String(_) => {}
                _ => {
                    let parser = match last_level {
                        LangItemKind::Parser(parser) => match parser.end() {
                            Some(end) => match end.eat(&mut self.cursor) {
                                Some(_) => {
                                    let parser = self.end_level()?;

                                    if let LangItemKind::Parser(parser) = parser {
                                        if let SearchMode::Parser = parser.search_mode() {
                                            if buf.len() > 0 {
                                                return Some(buf);
                                            }
                                        }
                                    }

                                    continue;
                                }
                                None => self.last_parser()?,
                            },
                            None => {
                                if self.levels.len() > 1 {
                                    let opt_before_last_item =
                                        self.levels.get(self.levels.len() - 2);

                                    match opt_before_last_item {
                                        Some(before_last_item) => match before_last_item {
                                            LangItemKind::String(str_type) => {
                                                match str_type.end().eat(&mut self.cursor) {
                                                    Some(_) => {
                                                        let parser = self.end_level()?;
                                                        self.end_level();

                                                        if let LangItemKind::Parser(parser) = parser
                                                        {
                                                            if let SearchMode::Parser =
                                                                parser.search_mode()
                                                            {
                                                                if buf.len() > 0 {
                                                                    return Some(buf);
                                                                }
                                                            }
                                                        }

                                                        continue;
                                                    }
                                                    None => self.last_parser()?,
                                                }
                                            }
                                            _ => parser,
                                        },
                                        None => parser,
                                    }
                                } else {
                                    parser
                                }
                            }
                        },
                        LangItemKind::Block(block) => {
                            match block.end().eat(&mut self.cursor) {
                                Some(_) => {
                                    self.end_level();

                                    let parser = self.last_parser()?;

                                    if let SearchMode::Parser = parser.search_mode() {
                                        if buf.len() > 0 {
                                            return Some(buf);
                                        }
                                    }

                                    continue;
                                }
                                None => {}
                            }

                            let parser = self.last_parser()?;

                            //FIXME: If parser end before blocks ends clear all blocks and last
                            //parser
                            parser
                        }
                        _ => unimplemented!(),
                    };

                    let parsers = parser.in_parser_parsers();
                    let blocks = parser.blocks();
                    let strs = parser.strings();

                    for str_type in strs {
                        match str_type.start().eat(&mut self.cursor) {
                            Some(_) => {
                                self.start_level(LangItemKind::String(str_type));

                                let parser = self.last_parser()?;

                                if let SearchMode::Parser = parser.search_mode() {
                                    if buf.len() > 0 {
                                        return Some(buf);
                                    }
                                }

                                continue 'outer;
                            }
                            None => {}
                        }
                    }

                    for block in blocks {
                        match block.start().eat(&mut self.cursor) {
                            Some(_) => {
                                self.start_level(LangItemKind::Block(block));

                                let parser = self.last_parser()?;

                                if let SearchMode::Parser = parser.search_mode() {
                                    if buf.len() > 0 {
                                        return Some(buf);
                                    }
                                }

                                continue 'outer;
                            }
                            None => {}
                        }
                    }

                    for parser in parsers {
                        match parser.start()?.eat(&mut self.cursor) {
                            Some(_) => {
                                self.start_level(LangItemKind::Parser(parser));

                                let prev_parser = self.levels.iter().rev().enumerate().find_map(
                                    |(i, parser)| match parser {
                                        LangItemKind::Parser(parser) if i != 0 => Some(parser),
                                        _ => None,
                                    },
                                )?;

                                if let SearchMode::Parser = prev_parser.search_mode() {
                                    if buf.len() > 0 {
                                        return Some(buf);
                                    }
                                }

                                continue 'outer;
                            }
                            None => {}
                        }
                    }
                    let parser = self.last_parser()?;

                    match parser.search_mode() {
                        SearchMode::Parser => {
                            let next_char = self.eat_next_chars(1);

                            match next_char {
                                Some(chr) if prev_char || !is_whitespace(&chr) => {
                                    prev_char = true;
                                    buf.push_str(&chr)
                                }
                                Some(_) => {}
                                None => {
                                    return None;
                                }
                            };
                        }
                        _ => {
                            self.eat_next_chars(1)?;
                        }
                    }
                }
            }
        }
    }
}

pub enum File<T> {
    PHP(Cursor<T>),
    JS(Cursor<T>),
    HTML(Cursor<T>),
}

impl<K: Read + Seek> From<File<K>> for Visitor<K> {
    fn from(ext: File<K>) -> Visitor<K> {
        Visitor {
            levels: vec![match ext {
                File::PHP(_) => LangItemKind::Parser(Box::new(HTMLParser)),
                File::JS(_) => LangItemKind::Parser(Box::new(JSParser)),
                File::HTML(_) => LangItemKind::Parser(Box::new(HTMLParser)),
            }],
            cursor: match ext {
                File::PHP(cur) => cur,
                File::JS(cur) => cur,
                File::HTML(cur) => cur,
            },
        }
    }
}

pub struct Position {
    line: u64,
    pos: u64,
}

pub struct Occurrence {
    start: Position,
    end: Position,
    levels: Vec<LangItemKind>,
}

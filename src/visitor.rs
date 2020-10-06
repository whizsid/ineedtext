use crate::parser::{LangItemKind, Parser};
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

    pub fn eat_next_chars(&mut self, len: usize)-> Option<String> {
        let mut buf = vec![0; len];

        let inner = self.cursor.get_mut();

        inner.read(&mut buf).ok()?;

        String::from_utf8(buf).ok()
    }
}

impl<T: Read + Seek> Iterator for Visitor<T> {
    type Item = String;

    fn next(&mut self) -> Option<String> {
        let mut buf = String::new();

        loop {
            let parser = self.last_parser()?;
            let last_level = self.last_level()?;

            match last_level {
                LangItemKind::String(str_type) => {
                    let in_str_parsers = parser.in_str_parsers();
                    let full_str_parsers = parser.in_full_str_parsers();

                    match str_type.end().eat(&mut self.cursor) {
                        Some(end) => {
                            self.levels.pop();
                            if buf.len() > 0 {
                                return Some(buf);
                            }
                        }
                        None => {
                            for parser in in_str_parsers {
                                let matcher = parser.start().unwrap();

                                match matcher.eat(&mut self.cursor) {
                                    Some(_)=>{
                                        self.levels.push(LangItemKind::Parser(parser));
                                        if buf.len()> 0 {
                                            return Some(buf);
                                        }
                                    }
                                    None=>{}
                                }
                            }

                            for parser in full_str_parsers {
                                let matcher = parser.string_check().unwrap();

                                                                 
                            }
                        }
                    }
                }
                LangItemKind::Parser(parser_type) => {
                    let in_parser_parsers = parser.in_parser_parsers();
                }
            }

            let next_char = self.eat_next_chars(1);

            match next_char {
                Some(chr)=> buf.push_str(&chr),
                None=>{break;}
            };
        }

        if buf.len()>0 {
            Some(buf)
        } else {
            None
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

use crate::parser::{LangItemKind, Parser, SearchMode, UniId};
use crate::parsers::html::HTMLParser;
use crate::parsers::js::JSParser;
use crate::parsers::php::{
    DoubleQuoteString, DoubleQuoteStringOnlyTrimmedEnd, PHPParser, SingleQuoteString,
    SingleQuoteStringOnlyTrimmedEnd,
};
use onig::Regex;
use std::fmt::{Debug, Formatter};
use std::io::{Cursor, Read, Seek, SeekFrom};

pub struct Visitor<T: Read + Seek> {
    levels: Vec<LangItemKind>,
    cursor: Cursor<T>,
    fake_cur_pos: u64,
    force_php_item: Option<UniId>,
}

pub fn can_ignore(txt: &str) -> bool {
    let txt = txt.replace("\n", " ").replace("\t", " ");

    let patterns = vec![
        Regex::new("(.*)[a-zA-Z0-9]\\.[a-zA-Z-0-9](.*)").unwrap(),
        Regex::new("(.*)[a-zA-Z0-9]_[a-zA-Z0-9](.*)").unwrap(),
        Regex::new("(.*)[a-zA-Z0-9]-[a-zA-Z0-9](.*)").unwrap(),
        Regex::new("(select|SELECT)(.*?)(from|FROM)(.*)").unwrap(),
        Regex::new("(insert|INSERT)(.*?)(into|INTO)(.*)").unwrap(),
        Regex::new("(DELETE|delete)([\\n\\s]+)(from|FROM)(.*)").unwrap(),
        Regex::new("(UPDATE|update)(.*?)(set|SET)(.*)").unwrap(),
        Regex::new("(.*)\\.php").unwrap(),
        Regex::new("^((?![a-zA-Z]).)*$").unwrap(),
    ];

    for pattern in patterns {
        if pattern.is_match(&txt) {
            return true;
        }
    }

    return false;
}

impl<T: Read + Seek> Visitor<T> {
    pub fn new<K: Read + Seek>(cur: Cursor<K>) -> Visitor<K> {
        Visitor {
            levels: vec![],
            cursor: cur,
            fake_cur_pos: 0,
            force_php_item: None,
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

        let next_pos = position - len as u64;
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

    pub fn update_cursor(&mut self, ate: usize) {
        let position = self.cursor.position() - ate as u64;
        self.fake_cur_pos = position;
    }

    pub fn cursor(&self) -> u64 {
        self.fake_cur_pos
    }

    pub fn get_inner(&mut self) -> &mut T {
        self.cursor.get_mut()
    }

    fn format_buf(&mut self, buf: &str, level: LangItemKind) -> Option<Occurrence> {
        let cursor = self.cursor();

        let end_trimmed = buf.trim_end_matches(|c: char| {
            !c.is_alphanumeric() && c != '.' && c != ')' && c != '\'' && c != '"'
        });

        let trimmed = end_trimmed.trim_start_matches(|c: char| !c.is_alphanumeric() && c != '(');

        let cursor_dif = (end_trimmed.len() - trimmed.len()) as u64;

        let (escape_quotes, only_trimmed_end) =
            if let UniId::PHPSingleQuoteString | UniId::PHPDoubleQuoteString = level.uni_id() {
                (
                    buf.len() == trimmed.len(),
                    end_trimmed.len() < buf.len() && end_trimmed.len() == trimmed.len(),
                )
            } else {
                (false, false)
            };

        let one = if escape_quotes { 1 } else { 0 };

        if trimmed.len() > 0 && !can_ignore(&trimmed) {
            Some(Occurrence {
                txt: String::from(trimmed),
                start_cursor: cursor + cursor_dif - if only_trimmed_end { 1 } else { one },
                end_cursor: cursor + one + end_trimmed.len() as u64,
                level: if only_trimmed_end {
                    match level.uni_id() {
                        UniId::PHPDoubleQuoteString => {
                            LangItemKind::String(Box::new(DoubleQuoteStringOnlyTrimmedEnd))
                        }
                        UniId::PHPSingleQuoteString => {
                            LangItemKind::String(Box::new(SingleQuoteStringOnlyTrimmedEnd))
                        }
                        _ => level,
                    }
                } else if escape_quotes {
                    LangItemKind::Parser(Box::new(PHPParser))
                } else {
                    level
                },
            })
        } else {
            None
        }
    }
}

impl<T: Read + Seek> Iterator for &mut Visitor<T> {
    type Item = Occurrence;

    fn next(&mut self) -> Option<Occurrence> {
        let mut buf = String::new();
        let mut prev_char: Option<String> = None;

        'outer: loop {
            let parser = self.last_parser()?;
            let last_level = self.last_level()?;

            match last_level {
                LangItemKind::String(str_type) if parser.search_mode().is_string() => {
                    let uni_id = str_type.uni_id().clone();
                    let in_str_parsers = parser.in_str_parsers();
                    let full_str_parsers = parser.in_full_str_parsers();

                    let escaped = if let Some(prev_char_a) = prev_char.clone() {
                        prev_char_a == "\\"
                    } else {
                        false
                    };

                    if !escaped {
                        match str_type.end().eat(&mut self.cursor) {
                            Some(ate) => {
                                let level = self.end_level()?;

                                self.update_cursor(buf.len() + ate.len());

                                match self.format_buf(&buf, level.clone()) {
                                    Some(buf) => {
                                        return Some(buf);
                                    }
                                    None => buf.clear(),
                                };
                                continue;
                            }
                            None => {}
                        }
                    }

                    for parser in in_str_parsers {
                        match parser.in_string_start() {
                            Some(matcher) => match matcher.eat(&mut self.cursor) {
                                Some(ate) => {
                                    self.start_level(LangItemKind::Parser(parser));
                                    self.update_cursor(ate.len() + buf.len());
                                    match self.format_buf(
                                        &buf,
                                        self.levels[self.levels.len() - 2].clone(),
                                    ) {
                                        Some(buf) => {
                                            return Some(buf);
                                        }
                                        None => buf.clear(),
                                    };
                                }
                                None => {}
                            },
                            None => {}
                        }
                    }

                    let next_char = self.eat_next_chars(1);

                    match next_char {
                        Some(chr) => {
                            prev_char = Some(chr.clone());
                            buf.push_str(&chr)
                        }
                        None => {
                            return None;
                        }
                    };

                    for parser in full_str_parsers {
                        let matcher = parser.string_check().unwrap();
                        if matcher.is_match(&buf) {
                            match uni_id {
                                UniId::PHPDoubleQuoteString | UniId::PHPSingleQuoteString => {
                                    self.force_php_item = Some(uni_id);
                                }
                                _ => {}
                            }
                            self.start_level(LangItemKind::Parser(parser));
                            self.go_back(buf.len());
                            buf.clear();
                            prev_char = None;
                            continue 'outer;
                        }
                    }
                }
                LangItemKind::String(_) => {}
                LangItemKind::Comment(comment) => match comment.end().eat(&mut self.cursor) {
                    Some(_) => {
                        self.end_level();
                        continue;
                    }
                    None => {
                        self.eat_next_chars(1);
                    }
                },
                _ => {
                    let force_php_level: Option<LangItemKind> = match &self.force_php_item {
                        Some(item) => match item {
                            UniId::PHPSingleQuoteString => {
                                Some(LangItemKind::String(Box::new(SingleQuoteString)))
                            }
                            UniId::PHPDoubleQuoteString => {
                                Some(LangItemKind::String(Box::new(DoubleQuoteString)))
                            }
                            _ => None,
                        },
                        None => None,
                    };
                    let parser = match last_level {
                        LangItemKind::Parser(parser) => match parser.end() {
                            Some(end) => {
                                match end.eat(&mut self.cursor).map(|i| Some(i)).unwrap_or_else(
                                    || self.last_parser()?.in_string_end()?.eat(&mut self.cursor),
                                ) {
                                    Some(ate) => {
                                        let parser = self.end_level()?;

                                        if let LangItemKind::Parser(parser) = parser {
                                            if let SearchMode::Parser = parser.search_mode() {
                                                self.update_cursor(ate.len() + buf.len());

                                                match self
                                                    .format_buf(&buf, LangItemKind::Parser(parser))
                                                {
                                                    Some(buf) => {
                                                        return Some(buf);
                                                    }
                                                    None => {
                                                        buf.clear();
                                                    }
                                                };
                                            }
                                        }

                                        continue;
                                    }
                                    None => self.last_parser()?,
                                }
                            }
                            None => parser,
                        },
                        LangItemKind::Block(block) => {
                            match block.end().eat(&mut self.cursor) {
                                Some(ate) => {
                                    let level = self.end_level()?;

                                    let parser = self.last_parser()?;

                                    if let SearchMode::Parser = parser.search_mode() {
                                        self.update_cursor(ate.len() + buf.len());

                                        match self.format_buf(
                                            &buf,
                                            force_php_level.unwrap_or(level.clone()),
                                        ) {
                                            Some(buf) => {
                                                return Some(buf);
                                            }
                                            None => {
                                                buf.clear();
                                            }
                                        };
                                    }

                                    continue;
                                }
                                None => {}
                            }

                            let parser = self.last_parser()?;

                            match parser.end() {
                                Some(end) => match end
                                    .eat(&mut self.cursor)
                                    .map(|i| Some(i))
                                    .unwrap_or_else(|| {
                                        self.last_parser()?.in_string_end()?.eat(&mut self.cursor)
                                    }) {
                                    Some(_) => {
                                        loop {
                                            match self.last_level()? {
                                                LangItemKind::Parser(_) => {
                                                    self.end_level();
                                                    break;
                                                }
                                                _ => {
                                                    self.end_level();
                                                }
                                            }
                                        }

                                        continue;
                                    }
                                    None => {}
                                },
                                None => {}
                            }

                            self.last_parser()?
                        }
                        _ => unimplemented!(),
                    };

                    let prev_str = self.levels.iter().rev().find_map(|i| match i {
                        LangItemKind::String(str_type) => Some(str_type),
                        _ => None,
                    });

                    let parsers = parser.in_parser_parsers();
                    let blocks = parser.blocks();
                    let strs = parser.strings();
                    let ignore = parser.ignore();
                    let comments = parser.comments();

                    if self.levels.len() > 1 {
                        let opt_before_str = self.levels.iter().rev().find(|i| {
                            if let LangItemKind::String(_) = i {
                                true
                            } else {
                                false
                            }
                        });

                        match opt_before_str {
                            Some(before_last_item) => match parser.uni_id() {
                                UniId::PHPParser => {}
                                _ => match before_last_item {
                                    LangItemKind::String(str_type) => {
                                        let escaped_char =
                                            if let Some(prev_char_a) = prev_char.clone() {
                                                prev_char_a == "\\"
                                            } else {
                                                false
                                            };

                                        if !escaped_char {
                                            match str_type.end().eat(&mut self.cursor) {
                                                Some(ate) => {
                                                    let parser: Option<Box<dyn Parser>>;
                                                    loop {
                                                        match self.end_level()? {
                                                            LangItemKind::Parser(removed) => {
                                                                parser = Some(removed);
                                                                break;
                                                            }
                                                            _ => {}
                                                        }
                                                    }

                                                    let str_type = self.end_level()?;

                                                    let parser = parser?;
                                                    if let SearchMode::Parser = parser.search_mode()
                                                    {
                                                        self.update_cursor(ate.len() + buf.len());

                                                        let level = match str_type.uni_id() {
                                                            UniId::PHPSingleQuoteString
                                                            | UniId::PHPDoubleQuoteString => {
                                                                str_type
                                                            }
                                                            _ => LangItemKind::Parser(parser),
                                                        };

                                                        match self.format_buf(
                                                            &buf,
                                                            force_php_level.unwrap_or(level),
                                                        ) {
                                                            Some(buf) => {
                                                                return Some(buf);
                                                            }
                                                            None => {
                                                                buf.clear();
                                                            }
                                                        };
                                                    }

                                                    self.force_php_item = None;

                                                    continue;
                                                }
                                                None => {}
                                            }
                                        }
                                    }
                                    _ => {}
                                },
                            },
                            None => {}
                        }
                    }

                    let last_level = self.last_level()?.clone();

                    match &last_level {
                        LangItemKind::String(_) => {}
                        _ => {
                            for comment in comments {
                                match comment.start().eat(&mut self.cursor) {
                                    Some(ate) => {
                                        self.start_level(LangItemKind::Comment(comment));
                                        let parser = self.last_parser()?;
                                        if let SearchMode::Parser = parser.search_mode() {
                                            self.update_cursor(ate.len() + buf.len());

                                            match self.format_buf(
                                                &buf,
                                                force_php_level.unwrap_or(last_level),
                                            ) {
                                                Some(buf) => {
                                                    return Some(buf);
                                                }
                                                None => {
                                                    buf.clear();
                                                }
                                            };
                                        }

                                        continue 'outer;
                                    }
                                    None => {}
                                }
                            }
                        }
                    }

                    for pattern in ignore {
                        match pattern.eat(&mut self.cursor) {
                            Some(ate) => {
                                let parser = self.last_parser()?;
                                let last_level = self.last_level()?.clone();
                                if let SearchMode::Parser = parser.search_mode() {
                                    self.update_cursor(ate.len() + buf.len());
                                    match self
                                        .format_buf(&buf, force_php_level.unwrap_or(last_level))
                                    {
                                        Some(buf) => {
                                            return Some(buf);
                                        }
                                        None => {
                                            buf.clear();
                                        }
                                    };
                                }

                                continue 'outer;
                            }
                            None => {}
                        }
                    }

                    let last_level = self.last_level()?;
                    match last_level {
                        LangItemKind::String(_) => {}
                        _ => {
                            for str_type in strs {
                                match str_type.start().eat(&mut self.cursor) {
                                    Some(ate) => {
                                        let last_level = self.last_level()?.clone();
                                        self.start_level(LangItemKind::String(str_type));

                                        let parser = self.last_parser()?;

                                        if let SearchMode::Parser = parser.search_mode() {
                                            self.update_cursor(ate.len() + buf.len());

                                            match self.format_buf(
                                                &buf,
                                                force_php_level.unwrap_or(last_level),
                                            ) {
                                                Some(buf) => {
                                                    return Some(buf);
                                                }
                                                None => buf.clear(),
                                            };
                                        }

                                        continue 'outer;
                                    }
                                    None => {}
                                }
                            }
                        }
                    }

                    for parser in parsers {
                        match parser.start()?.eat(&mut self.cursor) {
                            Some(ate) => {
                                let last_level = self.last_level()?.clone();
                                self.start_level(LangItemKind::Parser(parser));

                                let prev_parser = self.levels.iter().rev().enumerate().find_map(
                                    |(i, parser)| match parser {
                                        LangItemKind::Parser(parser) if i != 0 => Some(parser),
                                        _ => None,
                                    },
                                )?;

                                if let SearchMode::Parser = prev_parser.search_mode() {
                                    self.update_cursor(ate.len() + buf.len());

                                    match self
                                        .format_buf(&buf, force_php_level.unwrap_or(last_level))
                                    {
                                        Some(buf) => {
                                            return Some(buf);
                                        }
                                        None => {
                                            buf.clear();
                                        }
                                    };
                                }

                                continue 'outer;
                            }
                            None => {}
                        }
                    }

                    for block in blocks {
                        match block.start().eat(&mut self.cursor) {
                            Some(ate) => {
                                let last_level = self.last_level()?.clone();

                                self.start_level(LangItemKind::Block(block));

                                let parser = self.last_parser()?;

                                if let SearchMode::Parser = parser.search_mode() {
                                    self.update_cursor(ate.len() + buf.len());

                                    match self
                                        .format_buf(&buf, force_php_level.unwrap_or(last_level))
                                    {
                                        Some(buf) => {
                                            return Some(buf);
                                        }
                                        None => {
                                            buf.clear();
                                        }
                                    };
                                }

                                continue 'outer;
                            }
                            None => {}
                        }
                    }

                    match prev_str {
                        Some(str_type) => match str_type.end().eat(&mut self.cursor) {
                            Some(_) => {
                                loop {
                                    match self.last_level()? {
                                        LangItemKind::String(_) => {
                                            self.end_level();
                                            break;
                                        }
                                        _ => {
                                            self.end_level();
                                        }
                                    }
                                }
                                continue;
                            }
                            None => {}
                        },
                        None => {}
                    }

                    let parser = self.last_parser()?;

                    match parser.search_mode() {
                        SearchMode::Parser => {
                            let next_char = self.eat_next_chars(1);

                            match next_char {
                                Some(chr) => {
                                    prev_char = Some(chr.clone());
                                    buf.push_str(&chr)
                                }
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
            fake_cur_pos: 0,
            force_php_item: None,
        }
    }
}

#[derive(Clone)]
pub struct Occurrence {
    pub start_cursor: u64,
    pub end_cursor: u64,
    pub txt: String,
    pub level: LangItemKind,
}

impl Debug for Occurrence {
    fn fmt(&self, f: &mut Formatter<'_>) -> std::fmt::Result {
        f.write_str(&format!(
            "CS: {}, CE: {}, T: {}, L: {:?}",
            self.start_cursor, self.end_cursor, self.txt, self.level
        ))
    }
}

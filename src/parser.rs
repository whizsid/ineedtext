use onig::Regex;
use std::fmt::{Debug, Formatter};
use std::io::Cursor;
use std::io::{Read, Seek, SeekFrom};

pub struct Matcher(u16, Regex);

impl Matcher {
    pub fn new(max_size: u16, reg: Regex) -> Matcher {
        Matcher(max_size, reg)
    }

    /// Eating the next matching occurence
    pub fn eat<T: Read + Seek>(&self, cursor: &mut Cursor<T>) -> Option<String> {
        let start_position = cursor.position();

        let mut buf = vec![0; self.0 as usize];

        let file_mut = cursor.get_mut();
        file_mut.read(&mut buf).ok()?;
        for i in 0..buf.len() {
            let word = &buf[0..i + 1];
            let word = String::from_utf8(Vec::from(word)).ok()?;

            if self.1.is_match(&word) {
                let next_offset = start_position + (i + 1) as u64;

                file_mut.seek(SeekFrom::Start(next_offset)).ok()?;
                cursor.set_position(next_offset);
                return Some(word);
            }
        }

        file_mut.seek(SeekFrom::Start(start_position)).ok()?;
        cursor.set_position(start_position);
        None
    }
}

pub trait LangItem {
    fn start(&self) -> Matcher;

    fn end(&self) -> Matcher;

    fn id(&self) -> &str;
}

impl Debug for dyn LangItem {
    fn fmt(&self, f: &mut Formatter<'_>) -> std::fmt::Result {
        f.write_str(&format!("LangItem: {}", self.id()))
    }
}

pub enum LangItemKind {
    r#String(Box<dyn LangItem>),
    Parser(Box<dyn Parser>),
    Block(Box<dyn LangItem>),
}

impl Debug for LangItemKind {
    fn fmt(&self, f: &mut Formatter<'_>) -> std::fmt::Result {
        match self {
            Self::Parser(parser) => f.write_str(&format!("{:?}", parser)),
            Self::Block(li) | Self::String(li) => f.write_str(&format!("{:?}", li)),
        }
    }
}

pub enum SearchMode {
    Parser,
    r#String,
}

#[derive(Debug)]
pub enum Language {
    PHP,
    JS,
    HTML,
    SQL,
}

impl SearchMode {
    pub fn is_string(&self) -> bool {
        match self {
            SearchMode::String => true,
            _ => false,
        }
    }

    pub fn is_parser(&self) -> bool {
        match self {
            SearchMode::Parser => true,
            _ => false,
        }
    }
}

pub trait Parser {
    fn start(&self) -> Option<Matcher>;

    fn end(&self) -> Option<Matcher>;

    fn in_str_parsers(&self) -> Vec<Box<dyn Parser>> {
        vec![]
    }

    fn in_full_str_parsers(&self) -> Vec<Box<dyn Parser>> {
        vec![]
    }

    fn in_parser_parsers(&self) -> Vec<Box<dyn Parser>> {
        vec![]
    }

    fn strings(&self) -> Vec<Box<dyn LangItem>>;

    fn string_check(&self) -> Option<Regex>;

    fn blocks(&self) -> Vec<Box<dyn LangItem>>;

    fn search_mode(&self) -> SearchMode {
        SearchMode::String
    }

    fn lang(&self) -> Language;
}

impl Debug for dyn Parser {
    fn fmt(&self, f: &mut Formatter<'_>) -> std::fmt::Result {
        f.write_str(&format!("LangItem: {:?}", self.lang()))
    }
}

#[cfg(test)]
mod test {
    use super::*;
    use std::fs::File;
    use std::path::PathBuf;

    #[test]
    pub fn test_matcher() {
        let path = PathBuf::from("tests/test_matcher");
        let mut file = File::open(path).unwrap();

        let mut cursor = Cursor::new(&mut file);

        let matcher_success = Matcher::new(5, Regex::new("<?php").unwrap());
        let matcher_invalid = Matcher::new(4, Regex::new("<?php").unwrap());
        let matcher_failed = Matcher::new(5, Regex::new("<?html").unwrap());

        assert_eq!(
            matcher_success.eat(&mut cursor),
            Some(String::from("<?php"))
        );
        assert_eq!(
            matcher_success.eat(&mut cursor),
            Some(String::from("<?php"))
        );
        assert_eq!(matcher_invalid.eat(&mut cursor), None);
        assert_eq!(
            matcher_success.eat(&mut cursor),
            Some(String::from("<?php"))
        );
        assert_eq!(matcher_failed.eat(&mut cursor), None);
        assert_eq!(matcher_success.eat(&mut cursor), None);
    }
}

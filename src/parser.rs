use dyn_clone::DynClone;
use onig::Regex;
use std::fmt::{Debug, Formatter};
use std::io::Cursor;
use std::io::{Read, Seek, SeekFrom};

pub struct Matcher(Option<u16>, Regex);

pub const MAX_SEARCH_LENGTH: usize = 1000;

impl Matcher {
    pub fn new(max_size: Option<u16>, reg: Regex) -> Matcher {
        Matcher(max_size, reg)
    }

    /// Eating the next matching occurence
    pub fn eat<T: Read + Seek>(&self, cursor: &mut Cursor<T>) -> Option<String> {
        let start_position = cursor.position();
        let file_mut = cursor.get_mut();

        match self.0 {
            Some(size) => {
                let mut buf = vec![0; size as usize];

                file_mut.read(&mut buf).ok()?;
                for i in 0..buf.len() {
                    let word = &buf[0..i + 1];
                    let word = String::from_utf8_lossy(&word).to_string();

                    if self.1.is_match(&word) {
                        let next_offset = start_position + (i + 1) as u64;

                        file_mut.seek(SeekFrom::Start(next_offset)).ok()?;
                        cursor.set_position(next_offset);
                        return Some(word);
                    }
                }
            }
            None => {
                let mut main_buf = vec![];
                let mut i = 0;
                loop {
                    let mut buf = [0; 1];

                    file_mut.read(&mut buf).ok()?;

                    main_buf.push(buf[0]);

                    let word = String::from_utf8_lossy(&main_buf).to_string();

                    if self.1.is_match(&word) {
                        let next_offset = start_position + (i + 1) as u64;

                        file_mut.seek(SeekFrom::Start(next_offset)).ok()?;
                        cursor.set_position(next_offset);
                        return Some(word);
                    }

                    if i >= MAX_SEARCH_LENGTH {
                        break;
                    }

                    i += 1;
                }
            }
        }

        file_mut.seek(SeekFrom::Start(start_position)).ok()?;
        cursor.set_position(start_position);
        None
    }
}

pub trait LangItem: DynClone {
    fn start(&self) -> Matcher;

    fn end(&self) -> Matcher;

    fn id(&self) -> &str;

    fn uni_id(&self) -> UniId;
}

clone_trait_object!(LangItem);

impl Debug for dyn LangItem {
    fn fmt(&self, f: &mut Formatter<'_>) -> std::fmt::Result {
        f.write_str(&format!("LangItem: {}", self.id()))
    }
}

#[derive(Clone)]
pub enum LangItemKind {
    r#String(Box<dyn LangItem>),
    Parser(Box<dyn Parser>),
    Block(Box<dyn LangItem>),
    Comment(Box<dyn LangItem>),
}

impl LangItemKind {
    pub fn uni_id(&self) -> UniId {
        match self {
            LangItemKind::Parser(item) => item.uni_id(),
            LangItemKind::String(item)
            | LangItemKind::Block(item)
            | LangItemKind::Comment(item) => item.uni_id(),
        }
    }
}

impl Debug for LangItemKind {
    fn fmt(&self, f: &mut Formatter<'_>) -> std::fmt::Result {
        match self {
            Self::Parser(parser) => f.write_str(&format!("{:?}", parser)),
            Self::Block(li) | Self::String(li) | Self::Comment(li) => {
                f.write_str(&format!("{:?}", li))
            }
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
    CSS,
}

#[derive(Clone)]
pub enum UniId {
    PHPParser,
    PHPSingleQuoteString,
    PHPDoubleQuoteString,
    PHPSingleQuoteStringOnlyTrimmedEnd,
    PHPDoubleQuoteStringOnlyTrimmedEnd,
    PHPScope,
    PHPParentheses,
    PHPSingleLineComment,
    PHPMultiLineComment,
    JSParser,
    JSSingleQuoteString,
    JSDoubleQuoteString,
    JSBacktickString,
    JSScope,
    JSParentheses,
    JSSingleLineComment,
    JSMultiLineComment,
    CSSParser,
    CSSScope,
    HTMLParser,
    HTMLTag,
    HTMLComment,
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

pub trait Parser: DynClone {
    fn start(&self) -> Option<Matcher>;

    fn in_string_start(&self) -> Option<Matcher> {
        self.start()
    }

    fn end(&self) -> Option<Matcher>;

    fn in_string_end(&self) -> Option<Matcher> {
        self.end()
    }

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

    fn comments(&self) -> Vec<Box<dyn LangItem>> {
        vec![]
    }

    fn search_mode(&self) -> SearchMode {
        SearchMode::String
    }

    fn ignore(&self) -> Vec<Matcher> {
        vec![]
    }

    fn lang(&self) -> Language;

    fn uni_id(&self) -> UniId;
}

clone_trait_object!(Parser);

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

        let matcher_success = Matcher::new(Some(5), Regex::new("<\\?php").unwrap());
        let matcher_invalid = Matcher::new(Some(4), Regex::new("<\\?php").unwrap());
        let matcher_failed = Matcher::new(Some(5), Regex::new("<\\?html").unwrap());
        let matcher_without_size = Matcher::new(None, Regex::new("<\\?php").unwrap());

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
        assert_eq!(
            matcher_without_size.eat(&mut cursor),
            Some(String::from("<?php"))
        );
        assert_eq!(matcher_failed.eat(&mut cursor), None);
        assert_eq!(
            matcher_success.eat(&mut cursor),
            Some(String::from("<?php"))
        );
        assert_eq!(matcher_success.eat(&mut cursor), None);
    }
}

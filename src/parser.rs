use regex::Regex;
use std::io::Cursor;
use std::io::{Read, Seek, SeekFrom};

pub struct Matcher(u16, Regex);

impl Matcher {
    pub fn new(max_size: u16, reg: Regex) -> Matcher {
        Matcher (max_size, reg) 
    }

    /// Eating the next matching occurence
    pub fn eat<T: Read + Seek>(&self, cursor: &mut Cursor<T>)->Option<String> {
         let start_position = cursor.position();
 
         let mut buf = vec!(0;self.0 as usize);

         let file_mut = cursor.get_mut();
         file_mut.read(&mut buf).ok()?; 
         for i in 0.. buf.len() {
            let word = &buf[0..i+1];
            let word = String::from_utf8(Vec::from(word)).ok()?;

            if self.1.is_match(&word) {
                let next_offset = start_position + (i+1) as u64;

                file_mut.seek(SeekFrom::Start(next_offset)).ok()?;
                cursor.set_position(next_offset);
                return Some(word)
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
}

pub enum LangItemKind {
    r#String(Box<dyn LangItem>),
    Parser(Box<dyn Parser>)
}

pub enum WatchMode {
    Parser,
    r#String
}

pub trait Parser {
    fn start(&self) -> Option<Matcher>;

    fn end(&self) -> Option<Matcher>;

    fn in_str_parsers(&self)-> Vec<Box<dyn Parser>> {
        vec!()
    }

    fn in_full_str_parsers(&self) -> Vec<Box<dyn Parser>> {
        vec!()
    }

    fn in_parser_parsers(&self) -> Vec<Box<dyn Parser>> {
        vec!()
    }

    fn strings(&self)-> Vec<Box<dyn LangItem>>;

    fn string_check(&self)-> Option<Regex>;

    fn blocks(&self)-> Vec<(Matcher, Matcher)>;
}

#[cfg(test)]
mod test {
    use super::*;
    use std::fs::File;
    use std::path::PathBuf;

    #[test]
    pub fn test_matcher(){
        let path = PathBuf::from("tests/test_matcher");
        let mut file = File::open(path).unwrap();

        let mut cursor = Cursor::new(&mut file);

        let matcher_success = Matcher::new(5, Regex::new("<?php").unwrap());
        let matcher_invalid = Matcher::new(4, Regex::new("<?php").unwrap());
        let matcher_failed = Matcher::new(5, Regex::new("<?html").unwrap());

        assert_eq!(matcher_success.eat(&mut cursor), Some(String::from("<?php")));
        assert_eq!(matcher_success.eat(&mut cursor), Some(String::from("<?php")));
        assert_eq!(matcher_invalid.eat(&mut cursor), None);
        assert_eq!(matcher_success.eat(&mut cursor), Some(String::from("<?php")));
        assert_eq!(matcher_failed.eat(&mut cursor), None);
        assert_eq!(matcher_success.eat(&mut cursor), None);
    }
}

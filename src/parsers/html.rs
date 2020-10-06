use crate::parser::{Parser, LangItem,  Matcher};
use regex::Regex;
use crate::parsers::php::PHPParser;

pub struct HTMLParser;

pub struct SingleQuoteString;

impl Parser for HTMLParser {

    fn start(&self)->Option<Matcher> {
        None
    }

    fn end(&self)->Option<Matcher> {
        None
    }

    fn in_parser_parsers(&self)-> Vec<Box<dyn Parser>>{
        vec!(
            Box::new(PHPParser)    
        )
    }

    fn strings(&self)-> Vec<Box<dyn LangItem>> {
        vec!()
    }

    fn string_check(&self) -> Option<Regex> {
        Some(Regex::new("\\<(.*?)(\\s|)(.*?)\\>").unwrap())
    }

    fn blocks(&self)-> Vec<(Matcher, Matcher)> {
        vec!(
            (Matcher::new(250,Regex::new("\\<(?!.*(input|img|br|hr))(\\s|)(.*?)\\>").unwrap()), 
             Matcher::new(250, Regex::new("\\<<match1>(.*?)\\>").unwrap())),
        )
    }
}

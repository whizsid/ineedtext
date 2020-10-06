use crate::parser::{Parser, LangItem, ParserAllow, Matcher};
use regex::Regex;
use crate::parsers::php::{PHPParser};
use crate::parsers::html::HTMLParser;

pub struct JSParser;

pub struct SingleQuoteString;

impl LangItem for SingleQuoteString {
    fn start(&self)-> Matcher {
        Matcher::new(1,Regex::new("'").unwrap())
    }

    fn end(&self)-> Matcher {
        Matcher::new(1,Regex::new("'").unwrap())
    }
}

pub struct DoubleQuoteString;

impl LangItem for DoubleQuoteString {
    fn start(&self)-> Matcher {
        Matcher::new(1,Regex::new("\"").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(1,Regex::new("\"").unwrap())
    }
}

pub struct BacktickString;

impl LangItem for BacktickString {
    fn start(&self)-> Matcher {
        Matcher::new(1,Regex::new("`").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(1,Regex::new("`").unwrap())
    }
}



impl Parser for JSParser {

    fn start(&self)->Option<Matcher> {
        Some(Matcher::new(2,Regex::new("${").unwrap()))
    }

    fn end(&self)->Option<Matcher> {
        Some(Matcher::new(1,Regex::new("}").unwrap()))
    }

    fn parsers(&self)-> Vec<ParserAllow> {
        vec!(
            ParserAllow::String(Box::new(PHPParser)), 
            ParserAllow::Parser(Box::new(PHPParser)),
            ParserAllow::String(Box::new(HTMLParser))
        )
    }

    fn strings(&self)-> Vec<Box<dyn LangItem>> {
        vec!( Box::new(SingleQuoteString), Box::new(DoubleQuoteString), Box::new(BacktickString) )
    }

    fn string_check(&self) -> Option<Matcher> {
        Some(Matcher::new(10, Regex::new("(var|;|$)").unwrap()))
    }

    fn blocks(&self)-> Vec<(Matcher, Matcher)> {
        vec!(
            ( Matcher::new(1, Regex::new("{").unwrap()), Matcher::new(1, Regex::new("}").unwrap())),
            ( Matcher::new(1, Regex::new("(").unwrap()), Matcher::new(1, Regex::new(")").unwrap())),
        )
    }
}

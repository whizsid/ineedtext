use crate::parser::{Parser, LangItem, Matcher};
use crate::parsers::html::HTMLParser;
use regex::Regex;

pub struct PHPParser;

pub struct SingleQuoteString;

impl LangItem for SingleQuoteString {
    fn start(&self)-> Matcher {
        Matcher::new(1, Regex::new("'").unwrap())
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


impl Parser for PHPParser {

    fn start(&self)->Option<Matcher> {
        Some(Matcher::new(5,Regex::new("<?php").unwrap()))
    }

    fn end(&self)->Option<Matcher> {
        Some(Matcher::new(2,Regex::new("?>").unwrap()))
    }

    fn in_full_str_parsers(&self)-> Vec<Box<dyn Parser>> {
        vec!(Box::new(HTMLParser))
    }
    
    fn strings(&self)-> Vec<Box<dyn LangItem>> {
        vec!( Box::new(SingleQuoteString), Box::new(DoubleQuoteString) )
    }

    fn string_check(&self) -> Option<Regex> {
        None
    }

    fn blocks(&self)-> Vec<(Matcher, Matcher)> {
        vec!(
            ( Matcher::new(1, Regex::new("{").unwrap()), Matcher::new(1, Regex::new("}").unwrap())),
            ( Matcher::new(1, Regex::new("(").unwrap()), Matcher::new(1, Regex::new(")").unwrap())),
        )
    }
}

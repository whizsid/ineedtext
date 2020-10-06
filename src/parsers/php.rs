use crate::parser::{LangItem, Language, Matcher, Parser};
use crate::parsers::html::HTMLParser;
use onig::Regex;

pub struct PHPParser;

pub struct SingleQuoteString;

impl LangItem for SingleQuoteString {
    fn start(&self) -> Matcher {
        Matcher::new(1, Regex::new("'").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(1, Regex::new("'").unwrap())
    }

    fn id(&self) -> &str {
        "php_single_quote"
    }
}

pub struct DoubleQuoteString;

impl LangItem for DoubleQuoteString {
    fn start(&self) -> Matcher {
        Matcher::new(1, Regex::new("\"").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(1, Regex::new("\"").unwrap())
    }

    fn id(&self) -> &str {
        "php_double_quote"
    }
}

pub struct Scope;
impl LangItem for Scope {
    fn start(&self) -> Matcher {
        Matcher::new(1, Regex::new("{").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(1, Regex::new("}").unwrap())
    }

    fn id(&self) -> &str {
        "php_scope"
    }
}

pub struct Parentheses;
impl LangItem for Parentheses {
    fn start(&self) -> Matcher {
        Matcher::new(1, Regex::new("\\(").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(1, Regex::new("\\)").unwrap())
    }

    fn id(&self) -> &str {
        "php_parentheses"
    }
}

impl Parser for PHPParser {
    fn start(&self) -> Option<Matcher> {
        Some(Matcher::new(5, Regex::new("<\\?php").unwrap()))
    }

    fn end(&self) -> Option<Matcher> {
        Some(Matcher::new(2, Regex::new("\\?>").unwrap()))
    }

    fn in_full_str_parsers(&self) -> Vec<Box<dyn Parser>> {
        vec![Box::new(HTMLParser)]
    }

    fn strings(&self) -> Vec<Box<dyn LangItem>> {
        vec![Box::new(SingleQuoteString), Box::new(DoubleQuoteString)]
    }

    fn string_check(&self) -> Option<Regex> {
        None
    }

    fn blocks(&self) -> Vec<Box<dyn LangItem>> {
        vec![Box::new(Parentheses), Box::new(Scope)]
    }

    fn lang(&self) -> Language {
        Language::PHP
    }
}

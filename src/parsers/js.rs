use crate::parser::{LangItem, Language, Matcher, Parser};
use crate::parsers::html::HTMLParser;
use crate::parsers::php::PHPParser;
use onig::Regex;

pub struct JSParser;

pub struct SingleQuoteString;

impl LangItem for SingleQuoteString {
    fn start(&self) -> Matcher {
        Matcher::new(1, Regex::new("'").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(1, Regex::new("'").unwrap())
    }

    fn id(&self) -> &str {
        "js_single_quote"
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
        "js_double_quote"
    }
}

pub struct BacktickString;

impl LangItem for BacktickString {
    fn start(&self) -> Matcher {
        Matcher::new(1, Regex::new("`").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(1, Regex::new("`").unwrap())
    }

    fn id(&self) -> &str {
        "js_backtick"
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
        "js_scope"
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
        "js_parentheses"
    }
}

impl Parser for JSParser {
    fn start(&self) -> Option<Matcher> {
        Some(Matcher::new(2, Regex::new("\\${").unwrap()))
    }

    fn end(&self) -> Option<Matcher> {
        Some(Matcher::new(1, Regex::new("}").unwrap()))
    }

    fn in_full_str_parsers(&self) -> Vec<Box<dyn Parser>> {
        vec![Box::new(HTMLParser)]
    }

    fn in_str_parsers(&self) -> Vec<Box<dyn Parser>> {
        vec![Box::new(PHPParser)]
    }

    fn in_parser_parsers(&self) -> Vec<Box<dyn Parser>> {
        vec![Box::new(PHPParser)]
    }

    fn strings(&self) -> Vec<Box<dyn LangItem>> {
        vec![
            Box::new(SingleQuoteString),
            Box::new(DoubleQuoteString),
            Box::new(BacktickString),
        ]
    }

    fn string_check(&self) -> Option<Regex> {
        Some(Regex::new("(var|;|\\$)").unwrap())
    }

    fn blocks(&self) -> Vec<Box<dyn LangItem>> {
        vec![Box::new(Scope), Box::new(Parentheses)]
    }

    fn lang(&self) -> Language {
        Language::JS
    }
}

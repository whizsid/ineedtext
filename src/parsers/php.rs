use crate::parser::{LangItem, Language, Matcher, Parser, UniId};
use crate::parsers::html::HTMLParser;
use crate::parsers::css::CSSParser;
use onig::Regex;

#[derive(Clone)]
pub struct PHPParser;

#[derive(Clone)]
pub struct SingleQuoteString;

impl LangItem for SingleQuoteString {
    fn start(&self) -> Matcher {
        Matcher::new(Some(1), Regex::new("'").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(Some(1), Regex::new("'").unwrap())
    }

    fn id(&self) -> &str {
        "php_single_quote"
    }

    fn uni_id(&self)-> UniId {
        UniId::PHPSingleQuoteString
    }
}

#[derive(Clone)]
pub struct DoubleQuoteString;

impl LangItem for DoubleQuoteString {
    fn start(&self) -> Matcher {
        Matcher::new(Some(1), Regex::new("\"").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(Some(1), Regex::new("\"").unwrap())
    }

    fn id(&self) -> &str {
        "php_double_quote"
    }

    fn uni_id(&self)-> UniId {
        UniId::PHPDoubleQuoteString
    }
}

#[derive(Clone)]
pub struct DoubleQuoteStringOnlyTrimmedEnd;

impl LangItem for DoubleQuoteStringOnlyTrimmedEnd {
    fn start(&self) -> Matcher {
        Matcher::new(Some(1), Regex::new("\"").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(Some(1), Regex::new("\"").unwrap())
    }

    fn id(&self) -> &str {
        "php_double_quote"
    }

    fn uni_id(&self)-> UniId {
        UniId::PHPDoubleQuoteStringOnlyTrimmedEnd
    }
}

#[derive(Clone)]
pub struct SingleQuoteStringOnlyTrimmedEnd;

impl LangItem for SingleQuoteStringOnlyTrimmedEnd {
    fn start(&self) -> Matcher {
        Matcher::new(Some(1), Regex::new("\'").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(Some(1), Regex::new("\'").unwrap())
    }

    fn id(&self) -> &str {
        "php_double_quote"
    }

    fn uni_id(&self)-> UniId {
        UniId::PHPSingleQuoteStringOnlyTrimmedEnd
    }
}

#[derive(Clone)]
pub struct Scope;
impl LangItem for Scope {
    fn start(&self) -> Matcher {
        Matcher::new(Some(1), Regex::new("{").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(Some(1), Regex::new("}").unwrap())
    }

    fn id(&self) -> &str {
        "php_scope"
    }

    fn uni_id(&self)-> UniId {
        UniId::PHPScope
    }
}

#[derive(Clone)]
pub struct Parentheses;
impl LangItem for Parentheses {
    fn start(&self) -> Matcher {
        Matcher::new(Some(1), Regex::new("\\(").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(Some(1), Regex::new("\\)").unwrap())
    }

    fn id(&self) -> &str {
        "php_parentheses"
    }

    fn uni_id(&self)-> UniId {
        UniId::PHPParentheses
    }
}

impl Parser for PHPParser {
    fn start(&self) -> Option<Matcher> {
        Some(Matcher::new(Some(5), Regex::new("<\\?php").unwrap()))
    }

    fn end(&self) -> Option<Matcher> {
        Some(Matcher::new(Some(2), Regex::new("\\?>").unwrap()))
    }

    fn in_full_str_parsers(&self) -> Vec<Box<dyn Parser>> {
        vec![Box::new(HTMLParser), Box::new(CSSParser)]
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

    fn ignore(&self)-> Vec<Matcher> {
        vec!(
            Matcher::new(Some(40), Regex::new("\\[(.*?|)'(.*?|)'(.*?|)\\]").unwrap()),
            Matcher::new(Some(40), Regex::new("\\[(.*?|)\"(.*?|)\"(.*?|)\\]").unwrap())
            )
    }

    fn uni_id(&self)-> UniId {
        UniId::PHPParser
    }
}

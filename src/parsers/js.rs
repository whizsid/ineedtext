use crate::parser::{LangItem, Language, Matcher, Parser, UniId};
use crate::parsers::css::CSSParser;
use crate::parsers::html::HTMLParser;
use crate::parsers::php::PHPParser;
use onig::Regex;

#[derive(Clone)]
pub struct JSParser;

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
        "js_single_quote"
    }

    fn uni_id(&self) -> UniId {
        UniId::JSSingleQuoteString
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
        "js_double_quote"
    }

    fn uni_id(&self) -> UniId {
        UniId::JSDoubleQuoteString
    }
}

#[derive(Clone)]
pub struct BacktickString;

impl LangItem for BacktickString {
    fn start(&self) -> Matcher {
        Matcher::new(Some(1), Regex::new("`").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(Some(1), Regex::new("`").unwrap())
    }

    fn id(&self) -> &str {
        "js_backtick"
    }

    fn uni_id(&self) -> UniId {
        UniId::JSBacktickString
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
        "js_scope"
    }

    fn uni_id(&self) -> UniId {
        UniId::JSScope
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
        "js_parentheses"
    }

    fn uni_id(&self) -> UniId {
        UniId::JSParentheses
    }
}

#[derive(Clone)]
pub struct SingleLineComment;

impl LangItem for SingleLineComment {
    fn start(&self) -> Matcher {
        Matcher::new(Some(2), Regex::new("\\/\\/").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(Some(1), Regex::new("\\n").unwrap())
    }

    fn id(&self) -> &str {
        "js_single_line_comment"
    }

    fn uni_id(&self) -> UniId {
        UniId::JSSingleLineComment
    }
}

#[derive(Clone)]
pub struct MultiLineComment;

impl LangItem for MultiLineComment {
    fn start(&self) -> Matcher {
        Matcher::new(Some(2), Regex::new("\\/\\*").unwrap())
    }

    fn end(&self) -> Matcher {
        Matcher::new(Some(2), Regex::new("\\*\\/").unwrap())
    }

    fn id(&self) -> &str {
        "js_multi_line_comment"
    }

    fn uni_id(&self) -> UniId {
        UniId::JSMultiLineComment
    }
}

impl Parser for JSParser {
    fn start(&self) -> Option<Matcher> {
        Some(Matcher::new(Some(20), Regex::new("<script(.*?)>").unwrap()))
    }

    fn in_string_start(&self) -> Option<Matcher> {
        Some(Matcher::new(Some(2), Regex::new("\\${").unwrap()))
    }

    fn end(&self) -> Option<Matcher> {
        Some(Matcher::new(
            Some(20),
            Regex::new("</script(.*?|)>").unwrap(),
        ))
    }

    fn in_string_end(&self) -> Option<Matcher> {
        Some(Matcher::new(Some(1), Regex::new("}").unwrap()))
    }

    fn in_full_str_parsers(&self) -> Vec<Box<dyn Parser>> {
        vec![Box::new(HTMLParser), Box::new(CSSParser)]
    }

    fn in_str_parsers(&self) -> Vec<Box<dyn Parser>> {
        vec![Box::new(PHPParser), Box::new(JSParser), Box::new(CSSParser)]
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

    fn comments(&self) -> Vec<Box<dyn LangItem>> {
        vec![Box::new(SingleLineComment), Box::new(MultiLineComment)]
    }

    fn string_check(&self) -> Option<Regex> {
        Some(Regex::new("(.*?)(var|;|\\$)(.*?)").unwrap())
    }

    fn blocks(&self) -> Vec<Box<dyn LangItem>> {
        vec![Box::new(Scope), Box::new(Parentheses)]
    }

    fn lang(&self) -> Language {
        Language::JS
    }

    fn uni_id(&self) -> UniId {
        UniId::JSParser
    }
}

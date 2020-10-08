use crate::parser::{LangItem, Language, Matcher, Parser, SearchMode, UniId};
use crate::parsers::css::CSSParser;
use crate::parsers::js::JSParser;
use crate::parsers::php::PHPParser;
use onig::Regex;

#[derive(Clone)]
pub struct HTMLParser;

#[derive(Clone)]
pub struct Tag;

impl LangItem for Tag {
    fn start(&self) -> Matcher {
        Matcher::new(
            None,
            Regex::new("<(?!(input|img|br|hr))[A-Za-z]+(\\s|)(.*?)[^\\?]>").unwrap(),
        )
    }

    fn end(&self) -> Matcher {
        Matcher::new(Some(100), Regex::new("<\\/(.*?)>").unwrap())
    }

    fn id(&self) -> &str {
        "html_tag"
    }

    fn uni_id(&self)-> UniId {
        UniId::HTMLTag
    }
}

#[derive(Clone)]
pub struct Comment;
impl LangItem for Comment {
    fn start(&self) -> Matcher {
        Matcher::new(
            Some(4),
            Regex::new("<\\!--").unwrap(),
        )
    }

    fn end(&self) -> Matcher {
        Matcher::new(Some(3), Regex::new("-->").unwrap())
    }

    fn id(&self) -> &str {
        "html_comment"
    }

    fn uni_id(&self)-> UniId {
        UniId::HTMLComment
    }
}

impl Parser for HTMLParser {
    fn start(&self) -> Option<Matcher> {
        None
    }

    fn end(&self) -> Option<Matcher> {
        None
    }

    fn in_parser_parsers(&self) -> Vec<Box<dyn Parser>> {
        vec![Box::new(PHPParser), Box::new(JSParser), Box::new(CSSParser)]
    }

    fn strings(&self) -> Vec<Box<dyn LangItem>> {
        vec![]
    }

    fn string_check(&self) -> Option<Regex> {
        Some(Regex::new("(.*|)<[A-Za-z]+(\\s|)(.*?)[^\\?]>(.*|)").unwrap())
    }

    fn blocks(&self) -> Vec<Box<dyn LangItem>> {
        vec![Box::new(Comment),Box::new(Tag)]
    }

    fn search_mode(&self) -> SearchMode {
        SearchMode::Parser
    }

    fn lang(&self) -> Language {
        Language::HTML
    }

    fn ignore(&self) -> Vec<Matcher> {
        vec![
            Matcher::new(
                None,
                Regex::new("<(input|img|br|hr|\\!)(\\s|)(.*?|)[^\\?\\!]>").unwrap(),
            ),
            Matcher::new(Some(100), Regex::new("<\\/(.*?|)([^\\?\\!]|)>").unwrap())
        ]
    }

    fn uni_id(&self)-> UniId {
        UniId::HTMLParser
    }
}

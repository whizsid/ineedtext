use crate::parser::{LangItem, Language, Matcher, Parser, SearchMode};
use crate::parsers::php::PHPParser;
use onig::Regex;

pub struct HTMLParser;

pub struct Tag;

impl LangItem for Tag {
    fn start(&self) -> Matcher {
        Matcher::new(
            250,
            Regex::new("<(?!(input|img|br|hr))[A-Za-z]+(\\s|)(.*?)>").unwrap(),
        )
    }

    fn end(&self) -> Matcher {
        Matcher::new(250, Regex::new("</(.*?)>").unwrap())
    }

    fn id(&self) -> &str {
        "html_tag"
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
        vec![Box::new(PHPParser)]
    }

    fn strings(&self) -> Vec<Box<dyn LangItem>> {
        vec![]
    }

    fn string_check(&self) -> Option<Regex> {
        Some(Regex::new("<(.*?)(\\s|)(.*?)>").unwrap())
    }

    fn blocks(&self) -> Vec<Box<dyn LangItem>> {
        vec![Box::new(Tag)]
    }

    fn search_mode(&self) -> SearchMode {
        SearchMode::Parser
    }

    fn lang(&self) -> Language {
        Language::HTML
    }
}

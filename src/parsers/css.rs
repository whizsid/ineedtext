use crate::parser::{LangItem, Language, Matcher, Parser, UniId};
use onig::Regex;

#[derive(Clone)]
pub struct CSSParser;

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
        "css_block"
    }

    fn uni_id(&self) -> UniId {
        UniId::CSSScope
    }
}

impl Parser for CSSParser {
    fn start(&self) -> Option<Matcher> {
        Some(Matcher::new(Some(20), Regex::new("<style(.*?)>").unwrap()))
    }

    fn end(&self) -> Option<Matcher> {
        Some(Matcher::new(
            Some(20),
            Regex::new("</style(.*?|)>").unwrap(),
        ))
    }

    fn in_string_end(&self) -> Option<Matcher> {
        None
    }

    fn in_string_start(&self) -> Option<Matcher> {
        None
    }

    fn strings(&self) -> Vec<Box<dyn LangItem>> {
        vec![]
    }

    fn string_check(&self) -> Option<Regex> {
        Some(Regex::new("(.*|)[a-z-]+(\\s+|):(\\s+|)[a-z0-9\\(\\)-\\+]+(.*|)").unwrap())
    }

    fn blocks(&self) -> Vec<Box<dyn LangItem>> {
        vec![Box::new(Scope)]
    }

    fn lang(&self) -> Language {
        Language::CSS
    }

    fn uni_id(&self) -> UniId {
        UniId::CSSParser
    }
}

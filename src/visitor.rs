use crate::parser::{LangItemKind, Parser};
use crate::parsers::js::JSParser;
use crate::parsers::html::HTMLParser;
use std::io::Cursor;

pub struct Visitor<T> {
   levels:  Vec<LangItemKind>,
   cursor: Cursor<T>
}

impl <T> Visitor<T> {
    pub fn new<K>(cur: Cursor<K>)-> Visitor<K> {
        Visitor {
            levels: vec!(),
            cursor: cur
        }
    }

    pub fn last_level(&self)-> Option<&LangItemKind> {
        self.levels.last() 
    }

    pub fn start_level(&mut self, item_kind: LangItemKind){
        self.levels.push(item_kind);
    }

    pub fn end_level(&mut self)-> Option<LangItemKind> {
        self.levels.pop()
    }

    pub fn last_parser(&self) -> Option<&Box<dyn Parser>> {
         self.levels.iter().rev().find_map(|item| {
            if let LangItemKind::Parser(parser) = item {Some(parser)} else {None}
         })
    }
}

impl <T> Iterator for Visitor<T> {
    type Item = String;

    fn next(&mut self) -> Option<String> {
        None 
    }
}

pub enum File<T> {
    PHP(Cursor<T>),
    JS(Cursor<T>),
    HTML(Cursor<T>)
}

impl <K> From<File<K>> for Visitor<K> {
    fn from(ext: File<K>)->Visitor<K> {
        Visitor {
            levels: vec!(
                match ext {
                    File::PHP(_)=> LangItemKind::Parser(Box::new(HTMLParser)),
                    File::JS(_)=> LangItemKind::Parser(Box::new(JSParser)),
                    File::HTML(_)=> LangItemKind::Parser(Box::new(HTMLParser))
                }            
            ),
            cursor: match ext {
                    File::PHP(cur)=> cur,
                    File::JS(cur)=> cur,
                    File::HTML(cur)=> cur
                }
        } 
    }
}

pub struct Position {
    line: u64,
    pos: u64
}

pub struct Occurrence {
    start: Position,
    end: Position,
    levels: Vec<LangItemKind>
}

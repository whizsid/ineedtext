extern crate glob;
extern crate onig;

pub mod parser;
pub mod parsers;
pub mod visitor;

use glob::glob;
use std::fs::OpenOptions;
use std::io::Cursor;
use visitor::{File, Visitor};

fn main() {
    for entry in glob("tests/*.php").expect("Failed to read glob pattern") {
        match entry {
            Ok(path) => {
                let file = OpenOptions::new().read(true).open(&path).unwrap();

                let cursor = Cursor::new(file);

                let extension = path.extension();

                match extension {
                    Some(ext) => {
                        let file = match ext.to_str().unwrap() {
                            "php" => Some(File::PHP(cursor)),
                            "js" => Some(File::JS(cursor)),
                            "html" => Some(File::HTML(cursor)),
                            _ => None,
                        };

                        match file {
                            Some(file) => {
                                let visitor = Visitor::from(file);

                                for word in visitor {
                                    println!("{}", word);
                                }
                            }
                            None => {
                                println!("error: Unsupported file");
                            }
                        }
                    }
                    None => {
                        println!("error: Can not get a extension");
                    }
                }
            }
            Err(e) => println!("{:?}", e),
        }
    }
}

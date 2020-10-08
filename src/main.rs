extern crate glob;
extern crate onig;
#[macro_use]
extern crate dyn_clone;
#[macro_use]
extern crate indoc;

pub mod parser;
pub mod parsers;
pub mod visitor;

use glob::glob;
use parser::UniId;
use std::fs::{create_dir_all, OpenOptions};
use std::io::{BufRead, BufReader, Seek, SeekFrom};
use std::io::{Cursor, Write};
use std::path::PathBuf;
use visitor::{File, Occurrence, Visitor};

fn main() {
    let result_code_base = PathBuf::from("./results/");
    create_dir_all(&result_code_base).expect("Can not create result folder.");
    let mut po_file = OpenOptions::new().write(true).create(true).truncate(true).open(PathBuf::from("./results/translate-me.po")).unwrap();
    let header = indoc! {"
        #
        # Autogenerated by Nvision 0.0development
        #
        # language: English
        # locale: af_ZA
        # date: 2019-01-16 23:50-0500
        #
        msgid \"\"
        msgstr \"\"
        \"Project-Id-Version: SalesPlay POS Translation-0.000\\n\"
        \"POT-Creation-Date: 2019-01-16 23:42-0500\\n\"
        \"PO-Revision-Date: 2019-01-16 23:50-0500\\n\"
        \"Last-Translator: Nvision Team\\n\"
        \"Language-Team: Nvision IT Solutions <support@nvision.lk>\\n\"
        \"Language: English in United Kingdom\\n\"
        \"MIME-Version: 1.0\\n\"
        \"Content-Type: text/plain; charset=UTF-8\\n\"
        \"Content-Transfer-Encoding: 8bit\\n\"
        \"Plural-Forms: nplurals=2; plural=n != 1;\\n\"
    "};
    po_file.write_all(header.as_bytes()).expect("Can not write to PO file");
    for entry in glob("./*.php").expect("Failed to read glob pattern") {
        match entry {
            Ok(path) => {
                let path_str = path.to_str().expect("Can not convert the path to str");
                let file = OpenOptions::new().read(true).open(&path).unwrap();

                let cursor = Cursor::new(file);

                let extension = path.extension();

                let file_path = result_code_base.join(&path);
                let mut dir_path = file_path.clone();
                dir_path.pop();
                create_dir_all(&dir_path).expect("Can not create directory");
                let mut new_file = OpenOptions::new()
                    .create(true)
                    .truncate(true)
                    .write(true)
                    .open(&file_path)
                    .unwrap();

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
                                let mut occurences: Vec<Occurrence> = vec![];
                                let mut visitor = Visitor::from(file);
                                for word in &mut visitor {
                                    occurences.push(word);
                                }
                                let file = visitor.get_inner();
                                file.seek(SeekFrom::Start(0))
                                    .expect("Can not seek the file");

                                let f = BufReader::new(file);
                                let mut occurences_iter = occurences.iter();
                                let mut last_occurence: Option<&Occurrence> = None;
                                let mut cursor = 0;
                                for l in f.lines() {
                                    let mut line = l.expect("Can not read chars from line");
                                    line.push('\n');
                                    let chars = line.chars();

                                    for c in chars {
                                        match last_occurence {
                                            Some(occ) => {
                                                if occ.end_cursor == cursor {
                                                    let formated = match occ.level.uni_id() {
                                                        UniId::PHPScope
                                                        | UniId::PHPParser
                                                        | UniId::PHPParentheses => {
                                                            format!("local(\"{}\")", occ.txt)
                                                        }
                                                        UniId::PHPDoubleQuoteString => {
                                                            format!("\".local(\"{}\").\"", occ.txt)
                                                        }
                                                        UniId::PHPSingleQuoteString => {
                                                            format!("'.local('{}').'", occ.txt)
                                                        }
                                                        UniId::PHPSingleQuoteStringOnlyTrimmedEnd=> {
                                                            format!("local('{}').'", occ.txt)
                                                        }
                                                        UniId::PHPDoubleQuoteStringOnlyTrimmedEnd=> {
                                                            format!("local(\"{}\").\"", occ.txt)
                                                        }
                                                        _ => format!(
                                                            "<?php echo local('{}')  ?>",
                                                            occ.txt
                                                        ),
                                                    };

                                                    new_file
                                                        .write_all(formated.as_bytes())
                                                        .unwrap();

                                                    write!(po_file, "\n\n# {}:{}-{}:{:?}\nmsgid \"{}\"\nmsgstr \"{}\"", path_str, occ.start_cursor,  occ.end_cursor, occ.level, occ.txt, occ.txt );

                                                    match occurences_iter.next() {
                                                        Some(occ) => {
                                                            last_occurence = Some(occ);
                                                        }
                                                        None => {}
                                                    }
                                                }
                                            }
                                            None => match occurences_iter.next() {
                                                Some(oc) => {
                                                    last_occurence = Some(oc);
                                                }
                                                None => {}
                                            },
                                        };

                                        if let Some(occurence) = last_occurence {
                                            if occurence.start_cursor > cursor
                                                || occurence.end_cursor < cursor
                                            {
                                                write!(new_file, "{}", c);
                                            }
                                        }

                                        cursor += 1;
                                    }
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

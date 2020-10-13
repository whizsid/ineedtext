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
use std::env::args;
use std::fs::{create_dir_all, remove_dir_all, OpenOptions};
use std::io::{BufRead, BufReader, Read, Seek, SeekFrom};
use std::io::{Cursor, Write};
use std::path::PathBuf;
use visitor::{File, Occurrence, Visitor};

fn main() {
    let mut arg = args().into_iter();
    arg.next().unwrap();
    let input = &arg.next().unwrap_or(String::from("**/*.php"));

    let output = &arg.next().unwrap_or(String::from("./results"));

    let result_code_base = PathBuf::from(&output);
    let _removed = remove_dir_all(&result_code_base);
    create_dir_all(&result_code_base).expect("Can not create result folder.");
    let mut po_file = OpenOptions::new()
        .write(true)
        .create(true)
        .truncate(true)
        .open(PathBuf::from("./results/translate-me.po"))
        .unwrap();
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
    po_file
        .write_all(header.as_bytes())
        .expect("Can not write to PO file");
    let mut total = 0;
    println!("INFO: Counting Files");
    for _entry in glob(input).expect("Failed to read glob pattern") {
        total += 1;
    }
    println!("INFO: {} Files found", total);
    let mut i = 0;
    let mut word_count = 0;
    for entry in glob(input).expect("Failed to read glob pattern") {
        match entry {
            Ok(path) => {
                let path_str = path.to_str().expect("Can not convert the path to str");
                println!("INFO: [{}/{}] Analyzing file: {} ", i + 1, total, &path_str);
                let file = OpenOptions::new().read(true).open(&path).unwrap();

                let cursor = Cursor::new(file);

                let extension = path.extension();

                let file_path = result_code_base.join(&path);
                let file_path_str = file_path.to_str().unwrap();
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
                                    word_count += 1;
                                    println!(
                                        "INFO: [{}/{}] {} words found: {}",
                                        i + 1,
                                        total,
                                        word_count,
                                        word.txt
                                    );
                                    occurences.push(word);
                                }
                                let file = visitor.get_inner();
                                file.seek(SeekFrom::Start(0))
                                    .expect("Can not seek the file");

                                let mut f = BufReader::with_capacity(1, file);
                                let mut occurences_iter = occurences.iter();
                                let mut last_occurence: Option<&Occurrence> = None;
                                println!(
                                    "INFO: [{}/{}] Creating the new file {}",
                                    i + 1,
                                    total,
                                    file_path_str
                                );

                                let mut cursor: u64 = 0;
                                let mut line_number = 1;
                                let mut column = 1;
                                loop {
                                    match f.by_ref().fill_buf() {
                                        Ok(buf) => match buf.get(0) {
                                            Some(c) => {
                                                column += 1;
                                                let c = c.clone() as char;
                                                if c == '\n' {
                                                    line_number += 1;
                                                    column = 1
                                                }

                                                match last_occurence {
                                                    Some(occ) => {
                                                        if occ.end_cursor == cursor {
                                                            let txt = occ
                                                                .txt
                                                                .replace("\\'", "'")
                                                                .replace("\"", "\\\"")
                                                                .replace("\\\\\"", "\\\"");
                                                            let po_word = occ
                                                                .txt
                                                                .replace('"', "\\\"")
                                                                .replace("\\\\\"", "\\\"");
                                                            write!(
                                                                po_file,
                                                                "\n\n# File:{} Line:{} Column:{} Level:{:?}\nmsgid \"{}\"\nmsgstr \"{}\"",
                                                                path_str,
                                                                line_number,
                                                                if column < occ.txt.len() {0} else {column-occ.txt.len()},
                                                                occ.level,
                                                                po_word,
                                                                po_word
                                                            ).unwrap();

                                                            let formated = match occ.level.uni_id() {
                                                                UniId::PHPScope | UniId::PHPParser | UniId::PHPParentheses => {
                                                                    format!("gettext(\"{}\")", txt)
                                                                }
                                                                UniId::PHPDoubleQuoteString => {
                                                                    format!("\".gettext(\"{}\").\"", txt)
                                                                }
                                                                UniId::PHPSingleQuoteString => {
                                                                    format!("'.gettext(\"{}\").'", txt)
                                                                }
                                                                UniId::PHPSingleQuoteStringOnlyTrimmedEnd => {
                                                                    format!("gettext(\"{}\").'", txt)
                                                                }
                                                                UniId::PHPDoubleQuoteStringOnlyTrimmedEnd => {
                                                                    format!("gettext(\"{}\").\"", txt)
                                                                }
                                                                _ => format!("<?php echo gettext(\"{}\")  ?>", txt),
                                                            };

                                                            new_file
                                                                .write_all(formated.as_bytes())
                                                                .unwrap();

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

                                                if let Some(occ) = last_occurence {
                                                    if occ.start_cursor > cursor
                                                        || occ.end_cursor <= cursor
                                                    {
                                                        write!(new_file, "{}", c).unwrap();
                                                    }
                                                }

                                                cursor += 1;
                                            }
                                            _ => {
                                                break;
                                            }
                                        },
                                        _ => {
                                            break;
                                        }
                                    }

                                    f.consume(1);
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
        i += 1;
    }
}

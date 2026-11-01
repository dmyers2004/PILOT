# pilot

A PILOT interpreter in PHP, following **Commodore 64 PILOT**.

PILOT — *Programmed Inquiry, Learning, Or Teaching* — was John Starkweather's
1962 language for writing lessons. Every statement is one letter and a colon:
`T:` types, `A:` accepts, `M:` matches, `J:` jumps. Commodore's 1983 cartridge
added turtle graphics and the SID chip. This is that language, with the
graphics coming out as SVG and the sound as WAV.

```
T:WHAT IS THE CAPITAL OF FRANCE?
A:
M:PARIS
TY:YES -- THAT IS RIGHT.
TN:NO. IT IS PARIS.
E:
```

## Running it

No dependencies beyond PHP 8.1 or newer.

```bash
./bin/pilot examples/quiz.plt
```

```bash
./bin/pilot -e 'T:HELLO, WORLD!'
```

| option | |
| --- | --- |
| `-e, --eval CODE` | run `CODE` instead of a file |
| `--trace` | print each statement to stderr as it runs |
| `--fast` | ignore the delays asked for by `PA:` |
| `--echo` | echo accepted input, so piped runs read like live ones |
| `--c64` | draw on a 40x25 Commodore 64 screen, in ANSI colour |
| `--graphics FILE` | where `G:` drawings go (default `<program>.svg`) |
| `--no-graphics` | draw nothing to disk |
| `--sound FILE` | render `S:` notes to a WAV (default: silent) |
| `--seed N` | fix the seed for `RND()`, for repeatable runs |
| `--max-steps N` | give up after N statements |

Reaching the end of input at an `A:` ends the program, which is what you want
when piping a script in:

```bash
printf 'paris\ntokyo\nlima\n' | ./bin/pilot --echo examples/quiz.plt
```

## The language

### A line

```
[*LABEL] COMMAND [Y|N] [(expression)] : operand
```

Labels are `*NAME`, and may sit on their own line or in front of a statement.
A leading line number, as the Commodore editor produced, is accepted and
becomes a jump target of its own, so a listing typed in from a magazine runs
unchanged.

```
10 T:TYPED IN FROM A LISTING
20 J:10
```

### Statements

| | | |
| --- | --- | --- |
| `R:` | `REM:` | a remark; does nothing |
| `T:` | `TYPE:` | print the operand and a newline |
| `TH:` | | print it without the newline — for prompts |
| `A:` | `ACCEPT:` | read a line into the answer buffer |
| `M:` | `MATCH:` | test the buffer against patterns |
| `J:` | `JUMP:` | jump to a label |
| `U:` | `USE:` | call a label as a subroutine |
| `E:` | `END:` | return from `U:`, or end the program |
| `C:` | `COMPUTE:` | assign |
| `PA:` | `PAUSE:` | wait, in jiffies (1/60s) |
| `D:` | `DIM:` | declare arrays |
| `G:` | `GRAPHICS:` | drive the turtle |
| `S:` | `SOUND:` | play notes |
| `L:` | `LINK:` | hand over to another file |

### Conditioners

A `Y` or `N` after the command runs the statement only when the last `M:`
succeeded or failed. The flag persists until the next `M:`, so a single match
can steer a whole run of statements.

```
M:YES,SURE,OK
TY:GOOD.
CY:#SCORE=#SCORE+1
TN:MAYBE NEXT TIME.
```

A parenthesised expression is the other conditioner, and the two combine:

```
T(#SCORE>8):EXCELLENT.
JN(#TRIES<3):*ASK
```

### Variables

Numbers are `#NAME`, text is `$NAME`. Both are global, both start empty, and
names are not case-sensitive. `D:` makes arrays, indexed `0` to the size given:

```
D:#SCORE(10),$NAME(10)
C:#SCORE(3)=99
```

`A:` and `M:` leave their results in these:

| | | |
| --- | --- | --- |
| `$ANSWER` | `%B` | the last line accepted |
| `$MATCH` | `%M` | the text that matched |
| `$LEFT` | `%L` | what came before it |
| `$RIGHT` | `%R` | what came after it |
| `#MATCH` | | which pattern matched, counting from 1; 0 for none |

### Typing text

`T:` and `TH:` expand `#NUM` and `$STR` where they stand, and `#(...)` for a
whole expression. A backslash before `#`, `$`, `%` or `\` makes it literal.
Trailing spaces are kept, so `TH:YOUR NAME? ` leaves a gap for the answer.

```
T:HELLO $NAME, YOU ARE #AGE AND WILL BE #(#AGE+1) NEXT YEAR.
```

### Matching

`M:` holds a comma-separated list of patterns. Each is looked for anywhere in
the answer, ignoring case; the first one that fits wins. `*` stands for any run
of characters (greedily) and `?` for exactly one. A backslash makes the next
character literal, so `\,` is a comma inside a pattern rather than a separator,
and `\?` is a question mark rather than a wildcard.

What came before and after the match is the useful part. Matching `I AM` and
typing `%R` straight back is how `examples/eliza.plt` turns a statement into a
question — note that `%R` keeps its leading space:

```
M:I AM
TY:HOW LONG HAVE YOU BEEN%R?
```

```
A:
M:CAT,DOG,B*D
TY:YOU SAID #MATCH, WHICH WAS "%M"
```

### Jumping

`J:*LABEL` jumps and `U:*LABEL` calls, with `E:` returning. A list of labels is
a computed jump, picked by `#MATCH` — so one `M:` can branch three ways:

```
M:RED,GREEN,BLUE
J:*RED,*GREEN,*BLUE
T:NOT A COLOUR I KNOW
```

If `#MATCH` is 0, or past the end of the list, the jump falls through to the
next statement.

### Computing

```
C:#X=2+3*4
C:#X=#X+1;#Y=#X*2
C:$GREETING="HELLO, "+$NAME
C:$TITLE=THE MAIN MENU
```

Several assignments go on one line, separated by semicolons. A string
right-hand side is read as an expression when it can be, and as plain text
when it cannot — which is why both `$GREETING` and `$TITLE` above do what they
look like they do.

Operators, loosest first: `OR` `|`, `AND` `&`, `NOT` `!`, then `= <> < > <= >=`,
then `+ -`, then `* / %`, then unary `-`, then `^`.

`+` adds when both sides are numbers and joins them as text otherwise. It
decides by what a value *is*, not by what it looks like, so building a string
out of digits stays a string: `STR(5)+" "+STR(4)` is `5 4`, not `9`. Use
`VAL()` to go the other way. The other arithmetic operators read text as a
number, so `"10"*2` is 20.

Comparisons are looser, because judging what someone typed usually wants them
to be: two values that both look like numbers compare as numbers, and anything
else compares as text, ignoring case.

Functions: `ABS INT ROUND SGN SQR SIN COS TAN ATN EXP LOG MIN MAX RND`,
`LEN VAL STR ASC CHR UPPER LOWER TRIM LEFT RIGHT MID INSTR`, and `PI TIME`.
`RND()` gives a fraction from 0 to 1; `RND(N)` gives a whole number from 1 to N.
`MID` counts from 1, as BASIC does.

### Graphics

`G:` drives a turtle over the C64's 320×200 screen. It starts in the middle
facing up, with the pen down. Commas and semicolons are only there for
readability.

```
G:CLEAR, PEN LTBLUE
G:DRAW 50, TURN 90, DRAW 50
```

`CLEAR` `HOME` `GO n` `DRAW n` `MOVE n` `GOTO x y` `DRAWTO x y` `TURN n`
`TURNTO n` `PEN …` `PAPER …` `QUIT`. `TURN` is clockwise in degrees; `GO`
draws only if the pen is down, `DRAW` always draws and `MOVE` never does.
`PEN UP` and `PEN DOWN` raise and lower the pen; `PEN ERASE` draws in the
paper colour; and naming a colour changes the colour without touching whether
the pen is up or down. The colours are `0`–`15`, or `BLACK WHITE RED
CYAN PURPLE GREEN BLUE YELLOW ORANGE BROWN LTRED DKGREY GREY LTGREEN LTBLUE
LTGREY`.

Anything drawn is written out as an SVG when the program ends —
`examples/spiral.svg` is what `examples/spiral.plt` produces.

### Sound

`S:` takes pitch and duration pairs. A pitch is a note (`C`, `C#4`, `BB3`;
octave 4 if you leave it out), a frequency in Hz, or `R` for a rest. The
duration after it is in jiffies, written next to it or after a slash.

```
S:C4 12 D4 12 E4 12 F4 12 G4 24
S:G4/10 G4/10 C5/40
```

Nothing is audible unless you ask for a file:

```bash
./bin/pilot --sound=tune.wav examples/scales.plt
```

## The examples

| | |
| --- | --- |
| `hello.plt` | the smallest thing that asks a question |
| `quiz.plt` | a lesson: subroutines, two tries per question, a score |
| `lesson1.plt` | two files, chained with `L:`, sharing their variables |
| `guess.plt` | guess the number — `RND`, a loop, numeric conditioners |
| `mathdrill.plt` | times tables, five questions, a percentage at the end |
| `eliza.plt` | the doctor is in: `%R`, pattern order, a computed jump |
| `adventure.plt` | three rooms and a lamp, with the rooms as a computed jump |
| `hangman.plt` | string handling — `MID`, `LEN`, `INSTR`, built up a letter at a time |
| `battleship.plt` | a hidden fleet on a lettered grid — one array standing in for two dimensions |
| `risk.plt` | the forty-two territory map, dice combat, and an opponent |
| `spiral.plt` | a square spiral with a pentagram over it |
| `rosette.plt` | twelve circles round one point, cycling through the palette |
| `sine.plt` | the turtle plotting a computed curve |
| `scales.plt` | a scale and a fanfare, for `--sound` |

The three graphics examples have their drawings checked in beside them, so you
can see what they produce without running anything.

## The C64 screen

`--c64` puts the output on a Commodore 64 screen rather than letting it run
down the terminal: forty columns by twenty-five rows, light blue on blue
inside a light blue border, in the same sixteen colours the turtle draws
with. Text wraps at column 40, the screen scrolls at the bottom, and what you
type appears on the glass in upper case, the way the machine came out of the
box.

```bash
./bin/pilot --c64 examples/risk.plt
```

It needs a terminal that does 24-bit colour, which most now do. Programs
written for a wider terminal still run — they just wrap. `risk.plt` is laid
out to fit the forty columns exactly, board and all.

## Where this differs from the cartridge

The language is the C64 one. The machine underneath is not, so:

- **Graphics are a file, not a screen.** `G:` draws onto a 320×200 canvas in
  the 16 C64 colours and writes an SVG at the end, rather than painting a
  bitmap you watch fill in.
- **Sound is a file, not a SID.** One square-wave voice, rendered to WAV. No
  filters, no envelopes beyond a click-suppressing fade, no three voices.
- **The screen is a terminal**, unless you ask for `--c64`, which draws the
  40x25 screen and its border in colour. Even then there is no PETSCII, no
  colour control from inside `T:`, and no cursor addressing: the character
  set is whatever your terminal has.
- **Added for comfort:** `#(...)` in typed text, `?` as well as `*` in `M:`
  patterns, semicolon-separated assignments in `C:`, the string functions, and
  long command spellings (`TYPE:` for `T:`).
- **Not implemented:** the editor and its line-renumbering, cassette and disk
  file handling beyond `L:`, `#INKEY`-style polled input, and sprites.

## Layout

```
bin/pilot            the command
src/Parser.php       source text -> statements and labels
src/Expression.php   the expression language
src/Interpreter.php  the run loop
src/Store.php        variables, arrays, the match flag
src/Turtle.php       G: -> SVG
src/Sound.php        S: -> WAV
examples/            fourteen programs, from hello to risk
tests/run.php        the test suite
```

## Tests

```bash
php tests/run.php
```

Each case under `tests/cases` is a program, an optional `.in` of input, and the
output it should produce. `php tests/run.php --update` rewrites the expected
files, and `php tests/run.php match` runs just the cases whose name contains
`match`.

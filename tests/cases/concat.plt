R: + adds numbers and joins text, and decides which by the kind of value
R: it has, not by whether the text happens to look like a number.
C:#A=5;#B=4
T:NUMBERS ADD: #(#A+#B)
R: Building a string out of digits must not quietly do arithmetic.
C:$D=STR(#A)
C:$D=$D+" "+STR(#B)
C:$D=$D+" "+STR(3)
T:DIGITS JOIN: $D
C:$X="2";$Y="3"
T:STRINGS JOIN: #($X+$Y)
T:VAL CONVERTS: #(VAL($X)+VAL($Y))
R: The other operators still read text as numbers.
T:MINUS COERCES: #($X-0) #("10"*2)
R: Comparisons stay loose, which is what judging a typed answer needs.
T("10"=10):TEN MATCHES TEN
T($X<$Y):TWO IS LESS THAN THREE
E:

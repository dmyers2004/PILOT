R: The C64 graphics, by name, in typed text and in expressions.
T:BOX {UL}{HLINE}{UR} {VLINE} {LL}{HLINE}{LR}
T:SUITS {HEART}{DIAMOND}{CLUB}{SPADE}
T:BLOCKS {BLOCK}{UPPER}{LOWER}{LTSHADE}{MDSHADE}{DKSHADE}
R: A brace holding anything else is left exactly as written.
T:NOT A NAME: {WIBBLE} {} {12} AND ESCAPED \{HEART}
R: PET() takes the same names, so a name can be worked out.
T:FUNCTION #(PET("HEART")) AND BUILT UP #(PET("BAR"+STR(4)))
R: A quoted literal is literal text, so braces work there too.
C:$S="{HEART}AB"
T:LEN #(LEN($S)) LEFT #(LEFT($S,1)) MID #(MID($S,2,1)) RIGHT #(RIGHT($S,1))
T:INSTR #(INSTR($S,"B"))
R: Strings of graphics build up the way any other string does.
C:$BAR="";#I=1
*L
C:$BAR=$BAR+"{BLOCK}"
C:#I=#I+1
J(#I<=5):*L
T:BUILT $BAR IS #(LEN($BAR)) CHARACTERS
E:

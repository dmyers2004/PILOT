R: PETSCII GRAPHICS
R:
R: On the C64 these were on the fronts of the keys: hold Shift or the
R: Commodore key and you got a line, a corner, a block or a card suit.
R: There is no way to type one into a file of ASCII, so each has a name
R: instead -- {HLINE}, {HEART}, {BLOCK} -- and PET() takes the same
R: names, which means a name can be worked out rather than written.
R:
R: Run it with --c64 to see it laid out; without, the characters still
R: print, but nothing is placed.

SC:BORDER BLACK, PAPER BLUE, CLEAR

R: ---------------------------------------------- a box, drawn by hand
C:#BR=2;#BC=2;#BH=5;#BW=37
SC:INK LTBLUE
U:*BOX

SC:AT 4 6, INK WHITE
T:PETSCII GRAPHICS
SC:AT 5 6, INK YELLOW
T:{HEART} {DIAMOND} {CLUB} {SPADE}  {BALL} {CIRCLE} {CHECK} {PI}

R: ------------------------------------------------- what else there is
SC:AT 8 2, INK LTGREY
T:SHADES {LTSHADE}{LTSHADE}{MDSHADE}{MDSHADE}{DKSHADE}{DKSHADE}{BLOCK}
SC:AT 9 2
T:HALVES {UPPER}{LOWER}{LEFTH}{RIGHTH}  QUARTERS {QUL}{QUR}{QLL}{QLR}
SC:AT 10 2
T:DIAGONALS {DIAGUP}{DIAGDN}{DIAGX}{DIAGA}{DIAGB}
SC:AT 11 2
T:EIGHTHS {BAR1}{BAR2}{BAR3}{BAR4}{BAR5}{BAR6}{BAR7}{BAR8}

R: ------------------------------------------------------- a bar chart
SC:AT 13 2, INK WHITE
T:AND A CHART:
C:#N=1
*CHART
C:#V=RND(24)
C:$B="";#K=0
*FILL
C:$B=$B+"{BLOCK}"
C:#K=#K+1
J(#K<#V):*FILL
SC:AT #(14+#N) 2, INK #(2+#N)
T:$B
C:#N=#N+1
J(#N<=6):*CHART

SC:AT 23 2, INK WHITE
T:{ARROWLEFT} NAMES IN BRACES, OR PET("NAME").
E:

R: ------------------------------------------------------------- the box
R: A run of horizontals for the top and bottom, then the two sides a
R: row at a time. Nothing here the machine would not have done.
*BOX
C:$H="";#I=1
*BOXTOP
C:$H=$H+"{HLINE}"
C:#I=#I+1
J(#I<=#BW-2):*BOXTOP

SC:AT #BR #BC
T:{UL}$H{UR}
C:#I=1
*BOXSIDE
SC:AT #(#BR+#I) #BC
TH:{VLINE}
SC:AT #(#BR+#I) #(#BC+#BW-1)
TH:{VLINE}
C:#I=#I+1
J(#I<=#BH-2):*BOXSIDE
SC:AT #(#BR+#BH-1) #BC
T:{LL}$H{LR}
E:

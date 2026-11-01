R: SC: -- the screen, its colours and its cursor.
R: Run this one with --c64, or there is nothing much to see.

D:$CN(15)
C:$CN(0)=BLACK;$CN(1)=WHITE;$CN(2)=RED;$CN(3)=CYAN
C:$CN(4)=PURPLE;$CN(5)=GREEN;$CN(6)=BLUE;$CN(7)=YELLOW
C:$CN(8)=ORANGE;$CN(9)=BROWN;$CN(10)=LTRED;$CN(11)=DKGREY
C:$CN(12)=GREY;$CN(13)=LTGREEN;$CN(14)=LTBLUE;$CN(15)=LTGREY

SC:BORDER LTBLUE, PAPER BLUE, CLEAR
SC:AT 2 3, INK WHITE
T:THE SIXTEEN COLOURS

R: A bar of reversed spaces shows a colour as a block: in reverse video
R: the ink becomes the background, so a space is a solid patch of it.
C:#C=0
*BAR
SC:AT #(4+#C) 3
SC:INK #C, REVERSE ON
TH:      
SC:REVERSE OFF, INK LTGREY
TH:  #C $CN(#C)
C:#C=#C+1
J(#C<16):*BAR

R: Ink belongs to the cell it was typed in, as it did in colour RAM, so
R: changing it now leaves everything above exactly as it is.
SC:AT 21 3, INK YELLOW
T:SC: SETS INK, PAPER AND BORDER,
SC:AT 22 3
T:REVERSE ON/OFF, AT ROW COL,
SC:AT 23 3
T:CLEAR AND HOME.
SC:AT 24 3, INK WHITE
TH:PRESS RETURN TO CHANGE THE BORDER. 
A:
SC:BORDER RED, PAPER BLACK

R: Typing over a line does not rub out what was already on it, so the
R: tail of the longer prompt would show. Blank it first, as you would
R: have had to on the real machine.
SC:AT 24 3
TH:                                   
SC:AT 24 3, INK WHITE
TH:AND AGAIN TO FINISH. 
A:
E:

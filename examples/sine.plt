R: Plot a sine wave, to show the turtle taking computed coordinates.

G:CLEAR

R: The axis first, in a colour that stays out of the way.
G:PEN GREY, PEN UP, GOTO 20 100, PEN DOWN, DRAWTO 300 100

R: Then the curve, one short segment at a time.
G:PEN UP, GOTO 20 100, PEN DOWN, PEN LTGREEN
C:#X=20
*PLOT
C:#Y=100-70*SIN((#X-20)/45)
G:DRAWTO #X #Y
C:#X=#X+2
J(#X<=300):*PLOT

T:DRAWN. OPEN SINE.SVG TO SEE IT.
E:

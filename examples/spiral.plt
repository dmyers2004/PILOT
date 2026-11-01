R: Turtle graphics. The drawing is written out as spiral.svg.
R: The turtle starts in the middle of a 320x200 screen, facing up.

G:CLEAR, PEN LTBLUE

C:#SIDE=4
*SPIN
G:DRAW #SIDE, TURN 89
C:#SIDE=#SIDE+3
J(#SIDE<130):*SPIN

R: A five-pointed star over the top, in C64 yellow. The turtle goes to the
R: topmost point of the star, then heads off towards the point two along.
G:PEN UP, HOME, TURNTO 0, GO 55, TURNTO 162, PEN YELLOW
C:#I=0
*STAR
G:DRAW 105, TURN 144
C:#I=#I+1
J(#I<5):*STAR

T:DRAWN. OPEN SPIRAL.SVG TO SEE IT.
E:

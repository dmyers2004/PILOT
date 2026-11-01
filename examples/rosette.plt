R: Twelve circles around one point, each turned a little further.
R: A circle is just a polygon with enough sides not to look like one.

G:CLEAR
C:#RING=0

*RING
R: Cycle through six of the C64 colours as the rings go round.
G:PEN #(2+#RING%6)
C:#STEP=0
*CIRCLE
G:DRAW 6, TURN 10
C:#STEP=#STEP+1
J(#STEP<36):*CIRCLE

R: Thirty-six ten-degree turns bring the turtle back where it started,
R: so the next circle begins from the same point, turned 30 degrees on.
G:TURN 30
C:#RING=#RING+1
J(#RING<12):*RING

T:DRAWN. OPEN ROSETTE.SVG TO SEE IT.
E:

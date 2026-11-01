R: BATTLESHIP
R:
R: Four ships are hidden on an eight by eight grid. Call your shots by
R: column letter and row number -- C7, F2 -- until you have sunk them all.
R:
R: PILOT has no two-dimensional arrays, so the grid is one long array and
R: the square at row R column C lives at R*8+C. #BOARD holds which ship
R: is in a square, and #SHOT remembers where you have already fired.
R:
R: Run with --seed N to get the same layout every time.

D:#BOARD(63),#SHOT(63),#SIZE(4),#HITS(4),$SNAME(4)

C:#SIZE(1)=4;$SNAME(1)=BATTLESHIP
C:#SIZE(2)=3;$SNAME(2)=CRUISER
C:#SIZE(3)=3;$SNAME(3)=SUBMARINE
C:#SIZE(4)=2;$SNAME(4)=DESTROYER
C:#TURNS=0;#SUNK=0

U:*PLACEALL

T:BATTLESHIP
T:
T:FOUR SHIPS ARE HIDDEN ON THE GRID:
T:  BATTLESHIP (4), CRUISER (3), SUBMARINE (3), DESTROYER (2).
T:CALL YOUR SHOTS LIKE C7. TYPE QUIT TO GIVE UP.

R: ---------------------------------------------------------------- play
*TURN
U:*SHOW

*ASK
T:
TH:SHOT? 
A:
C:$G=UPPER(TRIM(%B))

M:QUIT,Q
JY:*GIVEUP

R: Split the answer into a column letter and a row number. INSTR gives 0
R: for a letter that is not on the board, so #COL comes out as -1.
C:$CL=LEFT($G,1);$RW=MID($G,2)
C:#COL=INSTR("ABCDEFGH",$CL)-1
C:#ROW=VAL($RW)-1
T(LEN($G)=0 | #COL<0 | #ROW<0 | #ROW>7):THAT IS NOT A SQUARE ON THE BOARD.
J(LEN($G)=0 | #COL<0 | #ROW<0 | #ROW>7):*ASK

C:#AT=#ROW*8+#COL
T(#SHOT(#AT)=1):YOU HAVE ALREADY FIRED AT $G.
J(#SHOT(#AT)=1):*ASK

C:#SHOT(#AT)=1;#TURNS=#TURNS+1
C:#HIT=#BOARD(#AT)

T(#HIT=0):MISS.
J(#HIT=0):*TURN

C:#HITS(#HIT)=#HITS(#HIT)+1
T(#HITS(#HIT)<#SIZE(#HIT)):HIT!
T(#HITS(#HIT)=#SIZE(#HIT)):HIT -- YOU SANK THE $SNAME(#HIT)!
C(#HITS(#HIT)=#SIZE(#HIT)):#SUNK=#SUNK+1
J(#SUNK<4):*TURN

U:*SHOW
T:
T:ALL FOUR SHIPS SUNK, IN #TURNS SHOTS.
T(#TURNS<=20):THAT IS SHARP SHOOTING.
E:

*GIVEUP
T:
T:THE FLEET WAS HERE:
C:#REVEAL=1
U:*SHOW
T:
T:YOU FIRED #TURNS SHOTS AND SANK #SUNK.
E:

R: ------------------------------------------------- draw the grid
R: A dot is a square you have not tried, X a hit and O a miss. On the
R: reveal at the end, a ship still afloat shows as a #.
*SHOW
T:
T:  A B C D E F G H
C:#DR=0
*ROW
C:$LINE=STR(#DR+1)+" "
C:#DC=0
*CELL
C:#DI=#DR*8+#DC
C(#SHOT(#DI)=1 & #BOARD(#DI)>0):$LINE=$LINE+"X "
C(#SHOT(#DI)=1 & #BOARD(#DI)=0):$LINE=$LINE+"O "
C(#SHOT(#DI)=0 & (#REVEAL=0 | #BOARD(#DI)=0)):$LINE=$LINE+". "
C(#SHOT(#DI)=0 & #REVEAL=1 & #BOARD(#DI)>0):$LINE=$LINE+"# "
C:#DC=#DC+1
J(#DC<8):*CELL
T:$LINE
C:#DR=#DR+1
J(#DR<8):*ROW
E:

R: ------------------------------------------------- put the fleet out
R: Pick a square and a direction, check the ship fits and that nothing
R: is already there, and start again if not.
*PLACEALL
C:#SHIP=1
*PLACE
*TRY
C:#DIR=RND(2);#LEN=#SIZE(#SHIP)
C:#PR=RND(8)-1;#PC=RND(8)-1
J(#DIR=1 & #PC+#LEN>8):*TRY
J(#DIR=2 & #PR+#LEN>8):*TRY

C:#K=0
*CHECK
C:#CR=#PR;#CC=#PC
C(#DIR=1):#CC=#PC+#K
C(#DIR=2):#CR=#PR+#K
J(#BOARD(#CR*8+#CC)>0):*TRY
C:#K=#K+1
J(#K<#LEN):*CHECK

C:#K=0
*PUT
C:#CR=#PR;#CC=#PC
C(#DIR=1):#CC=#PC+#K
C(#DIR=2):#CR=#PR+#K
C:#BOARD(#CR*8+#CC)=#SHIP
C:#K=#K+1
J(#K<#LEN):*PUT

C:#SHIP=#SHIP+1
J(#SHIP<=4):*PLACE
E:

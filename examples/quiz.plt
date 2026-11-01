R: A short quiz -- the kind of program PILOT was invented for.
R: Each question goes through the *ASK subroutine, which gives two tries.

C:#SCORE=0;#ASKED=0

T:A QUICK QUIZ ABOUT CAPITAL CITIES.
T:

C:$Q=WHAT IS THE CAPITAL OF FRANCE?;$A=PARIS
U:*ASK
C:$Q=WHAT IS THE CAPITAL OF JAPAN?;$A=TOKYO
U:*ASK
C:$Q=WHAT IS THE CAPITAL OF PERU?;$A=LIMA
U:*ASK

T:
T:YOU GOT #SCORE OUT OF #ASKED.
T(#SCORE=#ASKED):A PERFECT SCORE!
T(#SCORE=0):BETTER LUCK NEXT TIME.
E:

R: ---------------------------------------------------------------
*ASK
C:#ASKED=#ASKED+1;#TRIES=0
*AGAIN
T:$Q
A:
M:$A
TY:YES -- $A IS RIGHT.
CY:#SCORE=#SCORE+1
EY:
C:#TRIES=#TRIES+1
T(#TRIES<2):NO. HAVE ANOTHER GO.
J(#TRIES<2):*AGAIN
T:NO -- THE ANSWER IS $A.
E:

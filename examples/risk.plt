R: RISK
R:
R: The classic forty-two territory map, two players, dice combat.
R: You are A; the computer is B. Take every territory to win.
R:
R: Three things are worth looking at, because PILOT gives you no help
R: with any of them:
R:
R:   Adjacency.  There are no lists, so each territory's neighbours are
R:   held as a string of comma-wrapped numbers -- ",2,4,30," -- and two
R:   territories are neighbours when INSTR finds ",N," in one of them.
R:   The commas matter: without them "1" would be found inside "13".
R:
R:   Codes.      $CODES is every three-letter code joined with commas,
R:   so one INSTR turns what was typed into a territory number: every
R:   entry is four characters wide, so the number is (position-1)/4+1.
R:
R:   Sorting.    Dice are compared highest against highest, so they have
R:   to be sorted. Three conditioned swaps on one C: line each is the
R:   whole of it; unrolled, because there is nowhere to put a loop body.
R:
R: Run with --seed N to deal the same map every time.

D:$NAME(42),$CODE(42),$ADJ(42),#CONT(42),#OWNER(42),#ARMY(42),$F(42)
D:#CBON(6),$CNAME(6),$HOLD(6)

R: ------------------------------------------------------- the map
C:$CNAME(1)=N.AMERICA;#CBON(1)=5
C:$CNAME(2)=S.AMERICA;#CBON(2)=2
C:$CNAME(3)=EUROPE;#CBON(3)=5
C:$CNAME(4)=AFRICA;#CBON(4)=3
C:$CNAME(5)=ASIA;#CBON(5)=7
C:$CNAME(6)=AUSTRALIA;#CBON(6)=2

C:$CODE(1)=ALA;$NAME(1)=ALASKA;#CONT(1)=1;$ADJ(1)=",2,4,30,"
C:$CODE(2)=NWT;$NAME(2)=NW TERRITORY;#CONT(2)=1;$ADJ(2)=",1,3,4,5,"
C:$CODE(3)=GRN;$NAME(3)=GREENLAND;#CONT(3)=1;$ADJ(3)=",2,5,6,14,"
C:$CODE(4)=ALB;$NAME(4)=ALBERTA;#CONT(4)=1;$ADJ(4)=",1,2,5,7,"
C:$CODE(5)=ONT;$NAME(5)=ONTARIO;#CONT(5)=1;$ADJ(5)=",2,3,4,6,7,8,"
C:$CODE(6)=QUE;$NAME(6)=QUEBEC;#CONT(6)=1;$ADJ(6)=",3,5,8,"
C:$CODE(7)=WUS;$NAME(7)=WESTERN US;#CONT(7)=1;$ADJ(7)=",4,5,8,9,"
C:$CODE(8)=EUS;$NAME(8)=EASTERN US;#CONT(8)=1;$ADJ(8)=",5,6,7,9,"
C:$CODE(9)=CAM;$NAME(9)=CENTRAL AMERICA;#CONT(9)=1;$ADJ(9)=",7,8,10,"

C:$CODE(10)=VEN;$NAME(10)=VENEZUELA;#CONT(10)=2;$ADJ(10)=",9,11,12,"
C:$CODE(11)=BRA;$NAME(11)=BRAZIL;#CONT(11)=2;$ADJ(11)=",10,12,13,21,"
C:$CODE(12)=PER;$NAME(12)=PERU;#CONT(12)=2;$ADJ(12)=",10,11,13,"
C:$CODE(13)=ARG;$NAME(13)=ARGENTINA;#CONT(13)=2;$ADJ(13)=",11,12,"

C:$CODE(14)=ICE;$NAME(14)=ICELAND;#CONT(14)=3;$ADJ(14)=",3,15,16,"
C:$CODE(15)=SCA;$NAME(15)=SCANDINAVIA;#CONT(15)=3;$ADJ(15)=",14,16,17,20,"
C:$CODE(16)=GBR;$NAME(16)=GREAT BRITAIN;#CONT(16)=3;$ADJ(16)=",14,15,17,18,"
C:$CODE(17)=NEU;$NAME(17)=NORTHERN EUROPE;#CONT(17)=3;$ADJ(17)=",15,16,18,19,20,"
C:$CODE(18)=WEU;$NAME(18)=WESTERN EUROPE;#CONT(18)=3;$ADJ(18)=",16,17,19,21,"
C:$CODE(19)=SEU;$NAME(19)=SOUTHERN EUROPE;#CONT(19)=3;$ADJ(19)=",17,18,20,21,22,36,"
C:$CODE(20)=UKR;$NAME(20)=UKRAINE;#CONT(20)=3;$ADJ(20)=",15,17,19,27,34,36,"

C:$CODE(21)=NAF;$NAME(21)=NORTH AFRICA;#CONT(21)=4;$ADJ(21)=",11,18,19,22,23,24,"
C:$CODE(22)=EGY;$NAME(22)=EGYPT;#CONT(22)=4;$ADJ(22)=",19,21,23,36,"
C:$CODE(23)=EAF;$NAME(23)=EAST AFRICA;#CONT(23)=4;$ADJ(23)=",21,22,24,25,26,36,"
C:$CODE(24)=CON;$NAME(24)=CONGO;#CONT(24)=4;$ADJ(24)=",21,23,25,"
C:$CODE(25)=SAF;$NAME(25)=SOUTH AFRICA;#CONT(25)=4;$ADJ(25)=",23,24,26,"
C:$CODE(26)=MAD;$NAME(26)=MADAGASCAR;#CONT(26)=4;$ADJ(26)=",23,25,"

C:$CODE(27)=URA;$NAME(27)=URAL;#CONT(27)=5;$ADJ(27)=",20,28,34,35,"
C:$CODE(28)=SIB;$NAME(28)=SIBERIA;#CONT(28)=5;$ADJ(28)=",27,29,31,32,35,"
C:$CODE(29)=YAK;$NAME(29)=YAKUTSK;#CONT(29)=5;$ADJ(29)=",28,30,31,"
C:$CODE(30)=KAM;$NAME(30)=KAMCHATKA;#CONT(30)=5;$ADJ(30)=",1,29,31,32,33,"
C:$CODE(31)=IRK;$NAME(31)=IRKUTSK;#CONT(31)=5;$ADJ(31)=",28,29,30,32,"
C:$CODE(32)=MON;$NAME(32)=MONGOLIA;#CONT(32)=5;$ADJ(32)=",28,30,31,33,35,"
C:$CODE(33)=JAP;$NAME(33)=JAPAN;#CONT(33)=5;$ADJ(33)=",30,32,"
C:$CODE(34)=AFG;$NAME(34)=AFGHANISTAN;#CONT(34)=5;$ADJ(34)=",20,27,35,36,37,"
C:$CODE(35)=CHI;$NAME(35)=CHINA;#CONT(35)=5;$ADJ(35)=",27,28,32,34,37,38,"
C:$CODE(36)=MEA;$NAME(36)=MIDDLE EAST;#CONT(36)=5;$ADJ(36)=",19,20,22,23,34,37,"
C:$CODE(37)=IND;$NAME(37)=INDIA;#CONT(37)=5;$ADJ(37)=",34,35,36,38,"
C:$CODE(38)=SIA;$NAME(38)=SIAM;#CONT(38)=5;$ADJ(38)=",35,37,39,"

C:$CODE(39)=INO;$NAME(39)=INDONESIA;#CONT(39)=6;$ADJ(39)=",38,40,41,"
C:$CODE(40)=NGU;$NAME(40)=NEW GUINEA;#CONT(40)=6;$ADJ(40)=",39,41,42,"
C:$CODE(41)=WAU;$NAME(41)=W AUSTRALIA;#CONT(41)=6;$ADJ(41)=",39,40,42,"
C:$CODE(42)=EAU;$NAME(42)=E AUSTRALIA;#CONT(42)=6;$ADJ(42)=",40,41,"

R: Every code joined up, for turning what was typed into a number.
C:$CODES=","
C:#I=1
*MKCODES
C:$CODES=$CODES+$CODE(#I)+","
C:#I=#I+1
J(#I<=42):*MKCODES

U:*DEAL

T:R I S K
T:
T:42 TERRITORIES, ONE WORLD.
T:YOU ARE A. THE COMPUTER IS B.
T:TAKE THE LOT TO WIN.
T:
T:EACH TURN: REINFORCE, ATTACK, FORTIFY.
T:NAME LANDS BY THEIR 3-LETTER CODE.
C:#TURN=0

R: ==================================================== the main loop
*MAINLOOP
C:#TURN=#TURN+1
T:
T:========== TURN #TURN ==========
C:#WHO=1
U:*SHOW
U:*HUMANREINFORCE
U:*HUMANATTACK
J(#OVER=1):*GAMEOVER
U:*HUMANFORTIFY
U:*AITURN
J(#OVER=1):*GAMEOVER
J:*MAINLOOP

*GAMEOVER
U:*SHOW
T:
C:$TS=S
C(#TURN=1):$TS=
T(#WINNER=1):YOU HOLD THE WORLD, IN #TURN TURN$TS.
T(#WINNER=2):B TAKES THE WORLD IN #TURN TURN$TS.
E:

R: ============================================ your reinforcements
*HUMANREINFORCE
C:#WHO=1
U:*CALCREINF
T:
T:YOU GET #REINF ARMIES FOR #COUNT LANDS.
T($BONUSTEXT<>""):PLUS$BONUSTEXT
*HRLOOP
J(#REINF<1):*HRDONE
T:
TH:PLACE #REINF (CODE, OR CODE N)? 
A:
U:*PARSE1
J(#PT=0):*HRLOOP
T(#OWNER(#PT)<>1):THAT IS NOT YOURS.
J(#OWNER(#PT)<>1):*HRLOOP
C:#AMT=1
C($PARG<>""):#AMT=VAL($PARG)
C(#AMT<1):#AMT=1
C(#AMT>#REINF):#AMT=#REINF
C:#ARMY(#PT)=#ARMY(#PT)+#AMT;#REINF=#REINF-#AMT
T:#AMT TO $NAME(#PT).
J:*HRLOOP
*HRDONE
E:

R: ==================================================== your attacks
*HUMANATTACK
T:
T:ATTACK PHASE. 'FROM TO', OR END.
*HALOOP
T:
TH:ATTACK? 
A:
C:$G=UPPER(TRIM(%B))
M:END,DONE
JY:*HAEND
U:*PARSE2
J(#PT=0 | #PT2=0):*HALOOP
C:#FROM=#PT;#TO=#PT2
T(#OWNER(#FROM)<>1):$CODE(#FROM) IS NOT YOURS.
J(#OWNER(#FROM)<>1):*HALOOP
T(#OWNER(#TO)=1):YOU ALREADY HOLD $CODE(#TO).
J(#OWNER(#TO)=1):*HALOOP
T(#ARMY(#FROM)<2):$CODE(#FROM) HAS ONLY 1 ARMY.
J(#ARMY(#FROM)<2):*HALOOP
C:#ADJOK=INSTR($ADJ(#FROM),","+STR(#TO)+",")
T(#ADJOK=0):$CODE(#FROM) DOES NOT BORDER $CODE(#TO).
J(#ADJOK=0):*HALOOP
U:*BATTLE
J(#OVER=1):*HAEND
U:*SHOW
J:*HALOOP
*HAEND
E:

R: ============================================= your fortifying move
*HUMANFORTIFY
T:
TH:FORTIFY (FROM TO N), OR END? 
A:
C:$G=UPPER(TRIM(%B))
M:END,DONE
JY:*HFEND
U:*PARSE2
J(#PT=0 | #PT2=0):*HFEND
C:#FROM=#PT;#TO=#PT2
T(#OWNER(#FROM)<>1 | #OWNER(#TO)<>1):BOTH MUST BE YOURS.
J(#OWNER(#FROM)<>1 | #OWNER(#TO)<>1):*HFEND
C:#ADJOK=INSTR($ADJ(#FROM),","+STR(#TO)+",")
T(#ADJOK=0):THOSE DO NOT BORDER EACH OTHER.
J(#ADJOK=0):*HFEND
C:#AMT=1
C($PARG<>""):#AMT=VAL($PARG)
C(#AMT>#ARMY(#FROM)-1):#AMT=#ARMY(#FROM)-1
T(#AMT<1):NOTHING TO MOVE.
J(#AMT<1):*HFEND
C:#ARMY(#FROM)=#ARMY(#FROM)-#AMT;#ARMY(#TO)=#ARMY(#TO)+#AMT
T:#AMT MOVED FROM $CODE(#FROM) TO $CODE(#TO).
*HFEND
E:

R: ================================================ the computer's turn
*AITURN
T:
T:--- B'S TURN ---
C:#WHO=2
U:*CALCREINF
T:B GETS #REINF ARMIES.

R: Everything goes on B's strongest territory that faces one of yours.
C:#BEST=0;#BESTV=0-1;#AI=1
*AIPLACELOOP
C:#SKIP=1
C(#OWNER(#AI)=2):#SKIP=0
J(#SKIP=1):*AIPLACENEXT
U:*BORDERS
C(#ISBORDER=1 & #ARMY(#AI)>#BESTV):#BESTV=#ARMY(#AI);#BEST=#AI
*AIPLACENEXT
C:#AI=#AI+1
J(#AI<=42):*AIPLACELOOP
C(#BEST>0):#ARMY(#BEST)=#ARMY(#BEST)+#REINF
T(#BEST>0):B REINFORCES $NAME(#BEST).

R: Then up to six attacks, each one the biggest advantage it can find.
C:#AIN=0
*AIATTACK
U:*AIFIND
J(#AIF=0):*AIEND
C:#FROM=#AIF;#TO=#AIT
T:
T:B ATTACKS $CODE(#TO) FROM $CODE(#FROM).
U:*BATTLE
J(#OVER=1):*AIEND
C:#AIN=#AIN+1
J(#AIN<6):*AIATTACK
*AIEND
E:

R: Pick the attack with the largest edge: B's armies minus yours.
*AIFIND
C:#AIF=0;#AIT=0;#AIBEST=1
C:#X=1
*AIFX
C:#SKIP=1
C(#OWNER(#X)=2 & #ARMY(#X)>1):#SKIP=0
J(#SKIP=1):*AIFXNEXT
C:#Y=1
*AIFY
C:#EDGE=#ARMY(#X)-#ARMY(#Y)
C:#CAN=0
C(#OWNER(#Y)=1 & #EDGE>#AIBEST):#CAN=1
C(#CAN=1 & INSTR($ADJ(#X),","+STR(#Y)+",")=0):#CAN=0
C(#CAN=1):#AIBEST=#EDGE;#AIF=#X;#AIT=#Y
C:#Y=#Y+1
J(#Y<=42):*AIFY
*AIFXNEXT
C:#X=#X+1
J(#X<=42):*AIFX
E:

R: Does territory #AI touch one of the other player's?
*BORDERS
C:#ISBORDER=0;#BJ=1
*BORDERLOOP
C(#OWNER(#BJ)<>#OWNER(#AI) & INSTR($ADJ(#AI),","+STR(#BJ)+",")>0):#ISBORDER=1
C:#BJ=#BJ+1
J(#BJ<=42):*BORDERLOOP
E:

R: ================================================== one round of dice
R: #FROM attacks #TO. Three dice against two, highest against highest,
R: ties to the defender -- and the attacker moves in if the square falls.
*BATTLE
C:#NA=#ARMY(#FROM)-1
C(#NA>3):#NA=3
C:#ND=#ARMY(#TO)
C(#ND>2):#ND=2

C:#D1=0;#D2=0;#D3=0
C(#NA>=1):#D1=RND(6)
C(#NA>=2):#D2=RND(6)
C(#NA>=3):#D3=RND(6)
R: Sort the three descending. Unrolled, because a loop would need
R: somewhere to keep the body, and PILOT has nowhere to put one.
C(#D2>#D1):#SWAP=#D1;#D1=#D2;#D2=#SWAP
C(#D3>#D1):#SWAP=#D1;#D1=#D3;#D3=#SWAP
C(#D3>#D2):#SWAP=#D2;#D2=#D3;#D3=#SWAP

C:#E1=0;#E2=0
C(#ND>=1):#E1=RND(6)
C(#ND>=2):#E2=RND(6)
C(#E2>#E1):#SWAP=#E1;#E1=#E2;#E2=#SWAP

C:#CMP=#NA
C(#ND<#NA):#CMP=#ND

R: Show only the dice that were actually thrown.
C:$AD=STR(#D1)
C(#NA>=2):$AD=$AD+" "+STR(#D2)
C(#NA>=3):$AD=$AD+" "+STR(#D3)
C:$DD=STR(#E1)
C(#ND>=2):$DD=$DD+" "+STR(#E2)
T:  ATTACK $AD   DEFEND $DD
C(#D1>#E1):#ARMY(#TO)=#ARMY(#TO)-1
C(#D1<=#E1):#ARMY(#FROM)=#ARMY(#FROM)-1
C(#CMP>=2 & #D2>#E2):#ARMY(#TO)=#ARMY(#TO)-1
C(#CMP>=2 & #D2<=#E2):#ARMY(#FROM)=#ARMY(#FROM)-1
T:  $CODE(#FROM) #ARMY(#FROM) LEFT, $CODE(#TO) #ARMY(#TO) LEFT.

J(#ARMY(#TO)>0):*BATTLEEND
R: Taken. As many armies move in as there were dice.
C:#MOVE=#NA
C(#MOVE>#ARMY(#FROM)-1):#MOVE=#ARMY(#FROM)-1
C:#OWNER(#TO)=#OWNER(#FROM)
C:#ARMY(#TO)=#MOVE;#ARMY(#FROM)=#ARMY(#FROM)-#MOVE
T:  $CODE(#TO) FALLS -- #MOVE MOVE IN.
U:*CHECKWIN
*BATTLEEND
E:

*CHECKWIN
C:#WHO=1
U:*TALLY
C(#COUNT=42):#OVER=1;#WINNER=1
C(#COUNT=0):#OVER=1;#WINNER=2
E:

R: ============================================ counting and bonuses
R: #WHO goes in; #COUNT comes out.
*TALLY
C:#COUNT=0;#TI=1
*TALLYLOOP
C(#OWNER(#TI)=#WHO):#COUNT=#COUNT+1
C:#TI=#TI+1
J(#TI<=42):*TALLYLOOP
E:

R: #WHO goes in; #REINF comes out, with $BONUSTEXT explaining it.
*CALCREINF
U:*TALLY
C:#REINF=INT(#COUNT/3)
C(#REINF<3):#REINF=3
C:$BONUSTEXT=""
C:#BC=1
*BONUSLOOP
C:#BOWN=0;#BTOT=0;#BI=1
*BONUSINNER
C(#CONT(#BI)=#BC):#BTOT=#BTOT+1
C(#CONT(#BI)=#BC & #OWNER(#BI)=#WHO):#BOWN=#BOWN+1
C:#BI=#BI+1
J(#BI<=42):*BONUSINNER
C(#BOWN=#BTOT):#REINF=#REINF+#CBON(#BC)
C(#BOWN=#BTOT):$BONUSTEXT=$BONUSTEXT+" "+$CNAME(#BC)
C:#BC=#BC+1
J(#BC<=6):*BONUSLOOP
E:

R: =================================================== reading a reply
R: *PARSE1 wants one code and maybe a number: #PT and $PARG.
*PARSE1
C:$G=UPPER(TRIM(%B))
C:$PCODE=LEFT($G,3);$PARG=TRIM(MID($G,4))
U:*LOOKUP
C:#PT=#LOOK
T(#PT=0):NO TERRITORY CALLED $PCODE.
E:

R: *PARSE2 wants two codes and maybe a number: #PT, #PT2 and $PARG.
*PARSE2
C:$PCODE=LEFT($G,3)
U:*LOOKUP
C:#PT=#LOOK
C:$REST=TRIM(MID($G,4))
C:$PCODE=LEFT($REST,3)
U:*LOOKUP
C:#PT2=#LOOK
C:$PARG=TRIM(MID($REST,4))
T(#PT=0 | #PT2=0):GIVE TWO CODES, LIKE ALA NWT.
E:

R: $PCODE goes in; #LOOK comes out, 0 if there is no such code.
R: Every entry in $CODES is four characters wide, so the position of
R: ",XXX," gives the number straight away.
*LOOKUP
C:#LOOK=0
C:#LP=INSTR($CODES,","+$PCODE+",")
C(#LP>0):#LOOK=(#LP-1)/4+1
E:

R: ======================================================= the board
*SHOW
U:*FORMAT
T:
R: Four territories to a line keeps the board inside forty columns, so
R: it fits a real C64 screen -- try it with --c64.
T: $CNAME(1) #CBON(1) [$HOLD(1)]
T: $F(1) $F(2) $F(3) $F(4)
T: $F(5) $F(6) $F(7) $F(8)
T: $F(9)
T: $CNAME(2) #CBON(2) [$HOLD(2)]
T: $F(10) $F(11) $F(12) $F(13)
T: $CNAME(3) #CBON(3) [$HOLD(3)]
T: $F(14) $F(15) $F(16) $F(17)
T: $F(18) $F(19) $F(20)
T: $CNAME(4) #CBON(4) [$HOLD(4)]
T: $F(21) $F(22) $F(23) $F(24)
T: $F(25) $F(26)
T: $CNAME(5) #CBON(5) [$HOLD(5)]
T: $F(27) $F(28) $F(29) $F(30)
T: $F(31) $F(32) $F(33) $F(34)
T: $F(35) $F(36) $F(37) $F(38)
T: $CNAME(6) #CBON(6) [$HOLD(6)]
T: $F(39) $F(40) $F(41) $F(42)
E:

R: Each territory becomes a fixed eight characters, so the columns line
R: up however many armies are standing on it.
*FORMAT
C:#FI=1
*FMTLOOP
C:$OC=A
C(#OWNER(#FI)=2):$OC=B
C:$F(#FI)=$CODE(#FI)+" "+$OC+RIGHT("   "+STR(#ARMY(#FI)),3)
C:#FI=#FI+1
J(#FI<=42):*FMTLOOP

R: And which continents are held outright.
C:#HC=1
*HOLDLOOP
C:#HA=0;#HB=0;#HI=1
*HOLDINNER
C(#CONT(#HI)=#HC & #OWNER(#HI)=1):#HA=#HA+1
C(#CONT(#HI)=#HC & #OWNER(#HI)=2):#HB=#HB+1
C:#HI=#HI+1
J(#HI<=42):*HOLDINNER
C:$HOLD(#HC)=-
C(#HB=0):$HOLD(#HC)=A
C(#HA=0):$HOLD(#HC)=B
C:#HC=#HC+1
J(#HC<=6):*HOLDLOOP
E:

R: ==================================================== the first deal
R: Twenty-one territories each, one army on every one, then fourteen
R: more armies scattered over each player's holdings.
*DEAL
C:#I=1
*DEALCLEAR
C:#OWNER(#I)=0;#ARMY(#I)=1
C:#I=#I+1
J(#I<=42):*DEALCLEAR

C:#N=0
*DEALA
C:#R=RND(42)
J(#OWNER(#R)>0):*DEALA
C:#OWNER(#R)=1;#N=#N+1
J(#N<21):*DEALA

C:#I=1
*DEALB
C(#OWNER(#I)=0):#OWNER(#I)=2
C:#I=#I+1
J(#I<=42):*DEALB

C:#N=0
*SCATTERA
C:#R=RND(42)
J(#OWNER(#R)<>1):*SCATTERA
C:#ARMY(#R)=#ARMY(#R)+1;#N=#N+1
J(#N<14):*SCATTERA

C:#N=0
*SCATTERB
C:#R=RND(42)
J(#OWNER(#R)<>2):*SCATTERB
C:#ARMY(#R)=#ARMY(#R)+1;#N=#N+1
J(#N<14):*SCATTERB
E:

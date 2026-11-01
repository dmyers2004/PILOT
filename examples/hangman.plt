R: Hangman, built out of MID, LEN and INSTR.
R: The word is fixed so the example reads the same way every time.

C:$WORD=COMMODORE;$GUESSED="";#MISSES=0;#MAX=8

T:HANGMAN. ONE LETTER AT A TIME.
T:

*ROUND
R: Build the masked word a letter at a time. A conditioner on C: picks
R: between the letter and an underscore.
C:$SHOW="";#I=1
*BUILD
C:$CH=MID($WORD,#I,1)
C(INSTR($GUESSED,$CH)>0):$SHOW=$SHOW+$CH+" "
C(INSTR($GUESSED,$CH)=0):$SHOW=$SHOW+"_ "
C:#I=#I+1
J(#I<=LEN($WORD)):*BUILD

T:$SHOW    (#(#MAX-#MISSES) WRONG LEFT)
R: No underscores left means the word is finished.
J(INSTR($SHOW,"_")=0):*WON
J(#MISSES>=#MAX):*LOST

TH:LETTER? 
A:
C:$CH=UPPER(LEFT(%B,1))
J(LEN($CH)=0):*ROUND

T(INSTR($GUESSED,$CH)>0):YOU HAVE TRIED $CH ALREADY.
J(INSTR($GUESSED,$CH)>0):*ROUND

C:$GUESSED=$GUESSED+$CH
T(INSTR($WORD,$CH)>0):YES, THERE IS A $CH.
T(INSTR($WORD,$CH)=0):NO $CH IN IT.
C(INSTR($WORD,$CH)=0):#MISSES=#MISSES+1
J:*ROUND

*WON
T:
T:YOU GOT IT -- $WORD, WITH #MISSES WRONG.
E:

*LOST
T:
T:OUT OF GUESSES. THE WORD WAS $WORD.
E:

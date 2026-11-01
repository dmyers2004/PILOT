R: A small ELIZA.
R:
R: The trick is that M: leaves the text after the match in %R, so matching
R: "I AM" and typing %R straight back turns a statement into a question.
R: Note that %R keeps its leading space -- that is why there is no space
R: before it below.

T:TELL ME WHAT IS ON YOUR MIND.  (TYPE BYE TO STOP.)
T:
C:#N=0

*TOP
TH:> 
A:
C:#N=#N+1

M:BYE,GOODBYE,QUIT
JY:*BYE

M:I AM
TY:HOW LONG HAVE YOU BEEN%R?
JY:*TOP

M:I FEEL
TY:WHY DO YOU FEEL%R?
JY:*TOP

M:I THINK,I BELIEVE
TY:DO YOU REALLY THINK%R?
JY:*TOP

M:I WANT,I NEED
TY:WHAT WOULD IT MEAN TO YOU TO GET%R?
JY:*TOP

M:BECAUSE
TY:IS THAT THE REAL REASON?
JY:*TOP

M:MOTHER,FATHER,SISTER,BROTHER,FAMILY
TY:TELL ME MORE ABOUT YOUR FAMILY.
JY:*TOP

M:COMPUTER,MACHINE
TY:DOES IT BOTHER YOU THAT I AM A MACHINE?
JY:*TOP

R: A backslash makes ? a literal question mark instead of a wildcard.
M:\?
TY:WHY DO YOU ASK?
JY:*TOP

R: Nothing matched, so rotate through some stock replies. A computed jump
R: reads #MATCH, and nothing stops you setting #MATCH yourself.
C:#MATCH=1+#N%3
J:*S1,*S2,*S3
*S1
T:GO ON.
J:*TOP
*S2
T:WHAT DOES THAT SUGGEST TO YOU?
J:*TOP
*S3
T:I SEE. PLEASE CARRY ON.
J:*TOP

*BYE
T:GOODBYE. THAT WILL BE #(#N*5) DOLLARS.
E:

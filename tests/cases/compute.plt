R: C: and the expression language.
C:#A=2+3*4
C:#B=(2+3)*4
C:#C=2^3^2
C:#D=-2^2
T:#A #B #C #D
C:#E=17%5;#F=INT(7/2);#G=ABS(0-9);#H=SGN(0-4)
T:#E #F #G #H
C:#I=MIN(3,1,2);#J=MAX(3,1,2);#K=ROUND(3.14159,2)
T:#I #J #K
R: Comparisons give 1 or 0 and feed the (expression) conditioner.
C:#L=(3>2);#M=(3=4);#N=(2<>2)
T:#L #M #N
T((1<2)&(2<3)):AND WORKS
T((1>2)|(2<3)):OR WORKS
T(NOT(1>2)):NOT WORKS
R: A string right-hand side is an expression when it reads as one...
C:$X="ABC"+"DEF"
C:$Y=$X+" "+STR(#A)
R: ...and plain text when it does not.
C:$Z=HELLO THERE, FRIEND
T:$X / $Y / $Z
C:$P=LEFT("COMMODORE",6);$Q=RIGHT("COMMODORE",4);$R=MID("COMMODORE",3,2)
T:$P $Q $R LEN=#(LEN($X)) AT=#(INSTR("COMMODORE","ODO"))
R: Text compares ignore case; numbers compare as numbers.
T("abc"="ABC"):CASE IGNORED
T("10"=10):NUMERIC STRINGS MATCH NUMBERS
E:

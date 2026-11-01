R: D: gives an array 0..size, so D:#A(5) has six slots.
D:#A(5),$NAME(3)
C:#I=0
*FILL
C:#A(#I)=#I*#I
C:#I=#I+1
J(#I<6):*FILL
T:#A(0) #A(1) #A(2) #A(3) #A(4) #A(5)
C:$NAME(0)=ZERO;$NAME(1)=ONE
C:#K=1
T:[$NAME(0)] [$NAME(#K)] [$NAME(2)]
C:#A(2+1)=99
T:INDEXED BY EXPRESSION: #A(3)
E:

R: A: on its own fills the buffer; with a target it stores as well.
A:
T:BUFFER IS [%B]
A:$WHO
T:HELLO $WHO
A:#AGE
T:NEXT YEAR YOU ARE #(#AGE+1)
R: Several targets split the reply on commas.
A:$CITY,#YEAR
T:$CITY IN #YEAR
R: Text that is not a number reads as zero.
A:#N
T:N IS #N
E:

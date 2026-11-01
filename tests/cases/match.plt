R: M: against the answer buffer, with the pieces it leaves behind.
A:
M:CAT,DOG,BIRD
TY:MATCHED #MATCH: [%M] LEFT=[%L] RIGHT=[%R]
TN:NO MATCH, #MATCH
R: Wildcards: * is any run of characters, ? is exactly one.
M:B*D
TY:STAR MATCHED [$MATCH]
M:?OG
TY:QUERY MATCHED [$MATCH]
R: A match sets the flag for every later Y/N until the next M:.
JY:*YES
T:FLAG IS NO
E:
*YES
T:FLAG IS YES
E:

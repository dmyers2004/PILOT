R: Wildcards in M:, and the backslash that turns them off.
A:
M:W*T
TY:STAR [%M]
M:W?Y
TY:QUERY [%M]
R: A backslash makes the next character literal.
M:\?
TY:LITERAL QUESTION MARK [%M] LEFT=[%L]
M:\*
TN:NO LITERAL STAR HERE
R: ...and a comma, which would otherwise separate the patterns.
M:NOPE,IS\, THAT
TY:MATCHED PATTERN #MATCH [%M]
E:

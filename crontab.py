#!/usr/bin/python3.2
from subprocess import call
import os
file = "SET ME"
file2 = "SET ME"
fopen = open(file, "r+")
check = fopen.readlines()
fopen.close()

if any("Hash:" in s for s in check):
    with open(file2, "a") as myfile:
        myfile.write("\n")
        for line in check:
            if line != "\n":
                myfile.write(line)
                print(line, end ="")
    call(["php", "SET facebook.php", "1"])
    open(file,"w").close()
else:
    call(["php", "SET facebook.php", "0"])

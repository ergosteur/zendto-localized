function jirc_copytext(mytext) {
var COPY_TEXT=mytext
win=window.open("","JIRC_Copy_Windows","resizable=yes,height=250,width=800")
win.document.write('<html><head><title>JPilot jIRC text copy area</title></head>\n')
win.document.write('<center>Your copied text. </center><hr>\n')
win.document.write('<PRE>\n')
win.document.write( ''+COPY_TEXT+'')
win.document.write('</PRE>\n')
win.document.write('<BODY></HTML>\n')
win.document.close()
return true
                                                                                    
}


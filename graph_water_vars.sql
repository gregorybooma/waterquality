-- Function: graph_water_vars(text, text, text, text)

-- DROP FUNCTION graph_water_vars(text, text, text, text);

CREATE OR REPLACE FUNCTION graph_water_vars(text, text, text, text)
  RETURNS text AS
$BODY$

library(RPostgreSQL)
library(ggplot2)
library(scales)
library(RColorBrewer)

pg <- dbDriver("PostgreSQL")
con <- dbConnect(pg,dbname="[db name here]",user="[username here]",password="[password here]")

sitename <- arg4
varsunits <- unlist(strsplit(arg3,","))
timerange <- unlist(strsplit(arg2,","))
fileprefix <- arg1

filepath <- c("/mnt/var-www/mit/waterquality/tmp/")

infile <- paste(filepath,fileprefix,".csv",sep="")
#system(paste("chown postgres:www-drops ",infile,sep=""))

start <- noquote(paste("'",timerange[1],"'",sep=""))
end <- noquote(paste("'",timerange[2],"'",sep=""))

data <- read.csv(infile,stringsAsFactors=F)
data$data_time <- as.POSIXct(strptime(data$local_timestamp,"%Y-%m-%d %H:%M:%S"))

dovars <- length(varsunits)

for (i in 1:dovars) {

varsunitsi <- unlist(strsplit(varsunits[i],"@"))
vari <- varsunitsi[1]
unitsi <- varsunitsi[2]

outgraph <- paste(fileprefix,vari,unitsi,".png",sep="")
graphperm <- paste("chmod 0775 ",filepath,outgraph,sep="")

query <- paste("select unit_abbrev from wq_units where unit_code = '",unitsi,"';",sep="")

#pg <- dbDriver("PostgreSQL")
#con <- dbConnect(pg,dbname="[db name here]",user="[username here]",password="[password here]")
unitabbrevdf <- dbGetQuery(con,query)
unitabbrev <- unitabbrevdf[1,1]
#dbDisconnect(con)

title <- paste("Water Quality Observations at",sitename,"\n",timerange[1],"to",timerange[2],sep=" ")
xlabel <- "Date/Time"
ylabel <- paste(vari,' (',unitabbrev,')',sep="")

stamprange <- range(data$data_time)
stampdiff <- difftime(stamprange[2],stamprange[1])

p <- ggplot(data,aes_string(x="data_time",y=vari))

if (units(stampdiff) == "secs") {
p + geom_point(color="blue") + scale_x_datetime(breaks = date_breaks("1 sec")) + labs(list(title=title,x=xlabel,y=ylabel))
} else {
p + geom_point(color="blue") + labs(list(title=title,x=xlabel,y=ylabel))
}

ggsave(file=outgraph,path=filepath,height=4,units="in",dpi=100)
system(graphperm)
dev.off()
#need to figure out how we want to handle pdf output

} #for i in dovars

dbDisconnect(con)
print("done");

$BODY$
  LANGUAGE plr;

ALTER FUNCTION graph_water_vars(text, text, text, text)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION graph_water_vars(text, text, text, text) TO public;
GRANT EXECUTE ON FUNCTION graph_water_vars(text, text, text, text) TO postgres;
GRANT EXECUTE ON FUNCTION graph_water_vars(text, text, text, text) TO webservices;

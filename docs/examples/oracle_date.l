%option noyywrap
%option never-interactive
%option prefix="oracle_date"
%option outfile="lex.yy.c"

%{
#include <time.h>
#include <my_global.h>
#include <my_dbug.h>

#define YY_DECL static int my_scan(char **literal)

/* these are the different format tokens we may find 
   in an oracle to_char(date) format string */
enum orafmt {
    ORAFMT_EOF,
    ORAFMT_ERROR,
    ORAFMT_TEXT,
    ORAFMT_AD_DOTTED,
    ORAFMT_AM_DOTTED,
    ORAFMT_AD,
    ORAFMT_AM,
    ORAFMT_BC_DOTTED,
    ORAFMT_BC,
    ORAFMT_CC,
    ORAFMT_D,
    ORAFMT_DAY,
    ORAFMT_Day,
    ORAFMT_day,
    ORAFMT_DD,
    ORAFMT_DDD,
    ORAFMT_DY,
    ORAFMT_Dy,
    ORAFMT_dy,
    ORAFMT_E,
    ORAFMT_EE,
    ORAFMT_FM,
    ORAFMT_FX,
    ORAFMT_HH,
    ORAFMT_HH12,
    ORAFMT_HH24,
    ORAFMT_I,
    ORAFMT_IW,
    ORAFMT_IY,
    ORAFMT_IYY,
    ORAFMT_IYYYY,
    ORAFMT_J,
    ORAFMT_MI,
    ORAFMT_MM,
    ORAFMT_MON,
    ORAFMT_Mon,
    ORAFMT_mon,
    ORAFMT_MONTH,
    ORAFMT_Month,
    ORAFMT_month,
    ORAFMT_PM_DOTTED,
    ORAFMT_PM,
    ORAFMT_Q,
    ORAFMT_RM,
    ORAFMT_RR,
    ORAFMT_RRRR,
    ORAFMT_SCC,
    ORAFMT_SS,
    ORAFMT_SSSSS,
    ORAFMT_SYEAR,
    ORAFMT_SYYYY,
    ORAFMT_W,
    ORAFMT_WW,
    ORAFMT_Y,
    ORAFMT_Y_KOMMA1,
    ORAFMT_YEAR,
    ORAFMT_YY,
    ORAFMT_YYY,
    ORAFMT_YYYY,
};

%}

/* next is the actual scanner */

%%

"A.D."   return(ORAFMT_AD_DOTTED);
"A.M."   return(ORAFMT_AM_DOTTED);
"AD"     return(ORAFMT_AD);
"AM"     return(ORAFMT_AM);
"B.C."   return(ORAFMT_BC_DOTTED);
"BC"     return(ORAFMT_BC);
"CC"     return(ORAFMT_CC);
"D"      return(ORAFMT_D);
"DAY"    return(ORAFMT_DAY);
"Day"    return(ORAFMT_Day);
"day"    return(ORAFMT_day);
"DD"     return(ORAFMT_DD);
"DDD"    return(ORAFMT_DDD);
"DY"     return(ORAFMT_DY);
"Dy"     return(ORAFMT_Dy);
"dy"     return(ORAFMT_dy);
"E"      return(ORAFMT_E);
"EE"     return(ORAFMT_EE);
"FM"     return(ORAFMT_FM);
"FX"     return(ORAFMT_FX);
"HH"     return(ORAFMT_HH);
"HH12"   return(ORAFMT_HH12);
"HH24"   return(ORAFMT_HH24);
"I"      return(ORAFMT_I);
"IW"     return(ORAFMT_IW);
"IY"     return(ORAFMT_IY);
"IYY"    return(ORAFMT_IYY);
"IYYYY"  return(ORAFMT_IYYYY);
"J"      return(ORAFMT_J);
"MI"     return(ORAFMT_MI);
"MM"     return(ORAFMT_MM);
"MON"    return(ORAFMT_MON);
"Mon"    return(ORAFMT_Mon);
"mon"    return(ORAFMT_mon);
"MONTH"  return(ORAFMT_MONTH);
"Month"  return(ORAFMT_Month);
"month"  return(ORAFMT_month);
"P.M."   return(ORAFMT_PM_DOTTED);
"PM"     return(ORAFMT_PM);
"Q"      return(ORAFMT_Q);
"RM"     return(ORAFMT_RM);
"RR"     return(ORAFMT_RR);
"RRRR"   return(ORAFMT_RRRR);
"SCC"    return(ORAFMT_SCC);
"SS"     return(ORAFMT_SS);
"SSSSS"  return(ORAFMT_SSSSS);
"SYEAR"  return(ORAFMT_SYEAR);
"SYYYY"  return(ORAFMT_SYYYY);
"W"      return(ORAFMT_W);
"WW"     return(ORAFMT_WW);
"Y"      return(ORAFMT_Y);
"Y,YYY"  return(ORAFMT_Y_KOMMA1);
"YEAR"   return(ORAFMT_YEAR);
"YYY"    return(ORAFMT_YYY);
"YYYY"   return(ORAFMT_YYYY);
[[:space:]] *literal=yytext; return(ORAFMT_TEXT);
"-"         *literal=yytext; return(ORAFMT_TEXT);
"/"         *literal=yytext; return(ORAFMT_TEXT);
","         *literal=yytext; return(ORAFMT_TEXT);
"."         *literal=yytext; return(ORAFMT_TEXT);
";"         *literal=yytext; return(ORAFMT_TEXT);
":"         *literal=yytext; return(ORAFMT_TEXT);
\"[^\"]*\"  *literal=yytext; return(ORAFMT_TEXT);
.        return(ORAFMT_ERROR);

%%

/*
TODO:

- l10n i18n
- suffixes SP TH
- format modifiers FM FX
- and, of course, input and output itself ;)

*/

char *month_roman[13] = {"","I","II","III","VI","V","VI",
                         "VII","VIII","IX","X","XI","XII"};
char *MONTH_long[13]  = {"","JANUARY","FEBRUARY","MARCH","APRIL","MAY","JUNE",
                         "JULY","AUGUST","SEPTEMBER","OCTOBER","NOVEMBER","DECEMBER"};
char *MONTH_short[13] = {"","JAN","FEB","MAR","APR","MAY","JUN",
                         "JUL","AUG","SEP","OCT","NOV","DEC"};
char *Month_long[13]  = {"","January","February","March","April","May","June",
                         "July","August","September","October","November","December"};
char *Month_short[13] = {"","Jan","Feb","Mar","Apr","May","Jun",
                         "Jul","Aug","Sep","Oct","Nov","Dec"};
char *month_long[13]  = {"","january","february","march","april","may","june",
                         "july","august","september","october","november","december"};
char *month_short[13] = {"","jan","feb","mar","apr","may","jun",
                         "jul","aug","sep","oct","nov","dec"};
char *DAY_long[8]     = {"","MONDAY","TUESDAY","WEDNESDAY",
                         "THURSDAY","FRIDAY","SATURDAY","SUNDAY"};
char *Day_long[8]     = {"","Monday","Tuesday","Wednesday",
                         "Thursday","Friday","Saturday","Sunday"};
char *day_long[8]     = {"","monday","tuesday","wednesday",
                         "thursday","friday","saturday","sunday"};
char *DAY_short[8]     = {"","MON","TUE","WED","THU","FRI","SAT","SUN"};
char *Day_short[8]     = {"","Mon","Tue","Wed","Thu","Fri","Sat","Sun"};
char *day_short[8]     = {"","mon","tue","wed","thu","fri","sat","sun"};

int mdays[13]       = {31,28,31,30,31,30,31,31,30,31,30,31};
int mdays_total[14] = {0,0,31,59,90,120,151,181,212,243,273,304,334,365};

#define GREGOR_SDN_OFFSET         32045
#define DAYS_PER_5_MONTHS  153
#define DAYS_PER_4_YEARS   1461
#define DAYS_PER_400_YEARS 146097

static long int jd(int year, int month, int day) {
    /* JD 1 is Nov 25, 4714 B.C. Gregorian */

    /* There was no year zero 
       so we adjust B.C. dates to fill the gap*/
    if (year < 0) {
        year += 1;
    }

    /* Adjust the start of the year to march 1st
     This way a leap day is always the last day of the 
     previous year */
    if (month >= 3) {
        month -= 3;
    } else {
        month += 9;
        year--;
    }

    /* adjust year so that year 0 is on the last 400
       year boundary before JD 0 
       this is due to the 400 year leap year period
       within the gregorian calendar */
    year += 4800;

    /* now calculate the day */
    return (((year / 100) * DAYS_PER_400_YEARS) / 4
            + ((year % 100) * DAYS_PER_4_YEARS) / 4
            + (month * DAYS_PER_5_MONTHS + 2) / 5
            + day
            - GREGOR_SDN_OFFSET);
}

static int weekday(int year, int month, int day)
{
    int dow = (jd(year, month, day) + 1) % 7;

    return dow <= 0 ? dow + 7 : dow;
}

static int isoweek(int year, int yday)
{
    int first, week;
    first = weekday(year, 1, 1);

    week  = (yday + first - 2) / 7;

    if (first <= 4) 
        week++;
    else if (week == 0) 
        week = 53;

    return week;
}

static int isoyear(int year, int yday)
{
    return (isoweek(year, yday) == 53) ? year -1 : year;
}

static char * spoken_year(int year, int with_sign) {
    return ""; // TODO implement this
}

int ora_to_date(char *format, char *datestr, char *buffer) {
  return 0;
}

int ora_to_char(char *format, char *datestr, char *buffer, int maxlen) {
  int year, month, mday, wday, yday, hour, min, sec;
  int tmp;
  int leap_year;
  int iso_week, iso_year;
  int fill_mode = 1;
  int exact_match = 0;
  int done = 0;
  int len = 0, free = maxlen - 1;
  int hour12;
  char *p = buffer;

  DBUG_ENTER("ora_funcs::ora_to_char");

  if (6 != sscanf(datestr, "%d-%d-%d %d:%d:%d",
                  &year, &month, &mday, &hour, &min, &sec)) 
      return -1;

  leap_year = (year % 4 == 0) && ((year % 100 != 0) || (year % 400 == 0));
  wday = weekday(year, month, mday); 
  yday = mday + mdays_total[month] + (leap_year && month > 2);

  hour12 = hour % 12; 
  if (hour12 == 0) hour12 = 12;

  oracle_date_scan_string(format);

  
  while (!done && (free>0)) {
      char *text = NULL;
      len = 0;
      
      switch(my_scan(&text)) {
      case ORAFMT_EOF:   done=1; break;
      case ORAFMT_FM:    fill_mode = !fill_mode; break;
      case ORAFMT_FX:    exact_match = !exact_match; break;
      case ORAFMT_TEXT:  len = snprintf(p, free, "%s", text); break;
      case ORAFMT_D:     len = snprintf(p, free, "%d", wday+1); break; 
      case ORAFMT_DD:    len = snprintf(p, free, "%02d", mday); break;
      case ORAFMT_DDD:   len = snprintf(p, free, "%03d", yday); break;
      case ORAFMT_CC:    len = snprintf(p, free, "%d", abs(year)/100); break;
      case ORAFMT_SCC:   len = snprintf(p, free, "%d", year/100); break;
      case ORAFMT_SYYYY: len = snprintf(p, free, "%04d", year); break;
      case ORAFMT_RRRR:  
      case ORAFMT_YYYY:  len = snprintf(p, free, "%04d", abs(year)); break;
      case ORAFMT_YYY:   len = snprintf(p, free, "%03d", abs(year)%1000); break;
      case ORAFMT_RR:
      case ORAFMT_YY:    len = snprintf(p, free, "%02d", abs(year)%100); break;
      case ORAFMT_Y:     len = snprintf(p, free, "%01d", abs(year)%10); break;
      case ORAFMT_Y_KOMMA1: len = snprintf(p, free, "%d,%03d", year/1000, year%1000); break;
#if NONORA_EXTENSIONS
      case ORAFMT_RY:    len = snprintf(p, free, "%s", roman(year)); break;
#endif
      case ORAFMT_HH:   
      case ORAFMT_HH12:  len = snprintf(p, free, "%02d", hour12); break;
      case ORAFMT_HH24:  len = snprintf(p, free, "%02d", hour); break;
      case ORAFMT_MI:    len = snprintf(p, free, "%02d", min); break; 
      case ORAFMT_MM:    len = snprintf(p, free, "%02d", month); break;
      case ORAFMT_Q:     len = snprintf(p, free, "%d", (month+2)/3); break;
      case ORAFMT_RM:    len = snprintf(p, free, "%s", month_roman[month]); break;
      case ORAFMT_SS:    len = snprintf(p, free, "%02d", sec); break;
      case ORAFMT_MONTH: len = snprintf(p, free, fill_mode?"%-9s":"%s", MONTH_long[month]); break;
      case ORAFMT_Month: len = snprintf(p, free, fill_mode?"%-9s":"%s", Month_long[month]); break;
      case ORAFMT_month: len = snprintf(p, free, fill_mode?"%-9s":"%s", month_long[month]); break;
      case ORAFMT_MON:   len = snprintf(p, free, "%s", MONTH_short[month]); break;
      case ORAFMT_Mon:   len = snprintf(p, free, "%s", Month_short[month]); break;
      case ORAFMT_mon:   len = snprintf(p, free, "%s", month_short[month]); break;
      case ORAFMT_DAY:   len = snprintf(p, free, fill_mode?"%-9s":"%s", DAY_long[wday]); break;
      case ORAFMT_Day:   len = snprintf(p, free, fill_mode?"%-9s":"%s", Day_long[wday]); break;
      case ORAFMT_day:   len = snprintf(p, free, fill_mode?"%-9s":"%s", day_long[wday]); break;
      case ORAFMT_DY:    len = snprintf(p, free, "%s", DAY_short[wday]); break;
      case ORAFMT_Dy:    len = snprintf(p, free, "%s", Day_short[wday]); break;
      case ORAFMT_dy:    len = snprintf(p, free, "%s", day_short[wday]); break;
      case ORAFMT_W:     len = snprintf(p, free, "%d", (mday-1)/7 + 1); break;
      case ORAFMT_WW:    len = snprintf(p, free, "%d", (yday-1)/7 + 1); break;
      case ORAFMT_J:     len = snprintf(p, free, "%d", jd(year, month, mday)); break;
      case ORAFMT_I:     len = snprintf(p, free, "%d", isoyear(year, yday)%10); break;
      case ORAFMT_IW:    len = snprintf(p, free, "%d", isoweek(year, yday)); break;    
      case ORAFMT_IY:    len = snprintf(p, free, "%d", isoyear(year, yday)%100); break;
      case ORAFMT_IYY:   len = snprintf(p, free, "%d", isoyear(year, yday)%1000); break;
      case ORAFMT_IYYYY: len = snprintf(p, free, "%d", isoyear(year, yday)); break;
      case ORAFMT_SSSSS: len = snprintf(p, free, "%d", hour*3600 + min*60 + sec); break;
      case ORAFMT_BC:
      case ORAFMT_AD:    len = snprintf(p, free, "%s", (year>0) ? "AD" : "BC"); break;
      case ORAFMT_BC_DOTTED:
      case ORAFMT_AD_DOTTED: len = snprintf(p, free, "%s", (year>0) ? "A.D." : "B.C."); break;
      case ORAFMT_AM:    
      case ORAFMT_PM:    len = snprintf(p, free, "%s", (hour>12) ? "PM" : "AM"); break;
      case ORAFMT_AM_DOTTED:
      case ORAFMT_PM_DOTTED: len = snprintf(p, free, "%s", (hour>12) ? "P.M." : "A.M."); break;

      case ORAFMT_SYEAR: len = snprintf(p, free, "%s", spoken_year(year, 1)); break;
      case ORAFMT_YEAR:  len = snprintf(p, free, "%s", spoken_year(year, 0)); break;

      case ORAFMT_E:
      case ORAFMT_EE:

      case ORAFMT_ERROR:
      default:   
          len = snprintf(p, free, "??? %s", text); 
          return 0;
      }

      p += len;
      free -= len;
  }

  *p = '\0';

  DBUG_RETURN(1);
}

#ifdef TESTME
int main(int argc, char **argv) {
    char result[256];
    ora_to_char("YYYY Y,YYY", "2010-01-01 01:00:01", result, 256); puts(result);
    ora_to_char("YYYY Y,YYY", "0333-01-01 01:00:01", result, 256); puts(result);
}
#endif

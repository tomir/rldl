/*

StackBlur - a fast almost Gaussian Blur For Canvas

Version: 	0.5
Author:		Mario Klingemann
Contact: 	mario@quasimondo.com
Website:	http://www.quasimondo.com/StackBlurForCanvas
Twitter:	@quasimondo

In case you find this class useful - especially in commercial projects -
I am not totally unhappy for a small donation to my PayPal account
mario@quasimondo.de

Or support me on flattr: 
https://flattr.com/thing/72791/StackBlur-a-fast-almost-Gaussian-Blur-Effect-for-CanvasJavascript

Copyright (c) 2010 Mario Klingemann

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
*//* mod version for realdeal */var StackBlur=function(e,t,n){if(n<0||n==undefined)n=1024;var r=this,i=$.Deferred(),s=document.createElement("img");this.error=!1;s.onload=function(e){r.w=s.naturalWidth;r.h=s.naturalHeight;if(r.w>n||r.h>n){var t=(r.w>r.h?r.w:r.h)/n;r.w=Math.round(r.w/t);r.h=Math.round(r.h/t);r.radius=Math.round(r.radius/t)}r.canvas.width=r.w;r.canvas.height=r.h;try{var o=r.canvas.getContext("2d");o.clearRect(0,0,r.w,r.h);o.drawImage(s,0,0,r.w,r.h);r.blurRGB();i.resolve(r)}catch(e){i.resolve(r)}};s.crossOrigin="";this.canvas=document.createElement("canvas");this.w=0;this.h=0;if(isNaN(t)||t<1)t=1;this.radius=t;s.src=this.src=e;return i};StackBlur.prototype.getData=function(){try{return this.canvas.toDataURL("image/png")}catch(e){return this.src}};StackBlur.prototype.blurRGB=function(){return this.blurRGBA(!1)};StackBlur.prototype.blurRGBA=function(e){var t=function(){this.r=0;this.g=0;this.b=0;this.a=0;this.next=null},n=[512,512,456,512,328,456,335,512,405,328,271,456,388,335,292,512,454,405,364,328,298,271,496,456,420,388,360,335,312,292,273,512,482,454,428,405,383,364,345,328,312,298,284,271,259,496,475,456,437,420,404,388,374,360,347,335,323,312,302,292,282,273,265,512,497,482,468,454,441,428,417,405,394,383,373,364,354,345,337,328,320,312,305,298,291,284,278,271,265,259,507,496,485,475,465,456,446,437,428,420,412,404,396,388,381,374,367,360,354,347,341,335,329,323,318,312,307,302,297,292,287,282,278,273,269,265,261,512,505,497,489,482,475,468,461,454,447,441,435,428,422,417,411,405,399,394,389,383,378,373,368,364,359,354,350,345,341,337,332,328,324,320,316,312,309,305,301,298,294,291,287,284,281,278,274,271,268,265,262,259,257,507,501,496,491,485,480,475,470,465,460,456,451,446,442,437,433,428,424,420,416,412,408,404,400,396,392,388,385,381,377,374,370,367,363,360,357,354,350,347,344,341,338,335,332,329,326,323,320,318,315,312,310,307,304,302,299,297,294,292,289,287,285,282,280,278,275,273,271,269,267,265,263,261,259],r=[9,11,12,13,13,14,14,15,15,15,15,16,16,16,16,17,17,17,17,17,17,17,18,18,18,18,18,18,18,18,18,19,19,19,19,19,19,19,19,19,19,19,19,19,19,20,20,20,20,20,20,20,20,20,20,20,20,20,20,20,20,20,20,21,21,21,21,21,21,21,21,21,21,21,21,21,21,21,21,21,21,21,21,21,21,21,21,21,21,21,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,22,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,23,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24,24],i=this.canvas.getContext("2d"),s;try{s=i.getImageData(0,0,this.w,this.h)}catch(o){console.log(o);return!1}var u=s.data,a,f,l,c,h,p,d,v,m,g,y,b,w,E,S,x,T,N,C,k,L,A,O,M,_=this.radius+this.radius+1,D=this.w<<2,P=this.w-1,H=this.h-1,B=this.radius+1,j=B*(B+1)/2,F=new t,I=F;for(l=1;l<_;l++){I=I.next=new t;if(l==B)var q=I}I.next=F;var R=null,U=null;d=p=0;var z=n[this.radius],W=r[this.radius];if(e===!1){for(f=0;f<this.h;f++){x=T=N=v=m=g=0;b=B*(k=u[p]);w=B*(L=u[p+1]);E=B*(A=u[p+2]);v+=j*k;m+=j*L;g+=j*A;I=F;for(l=0;l<B;l++){I.r=k;I.g=L;I.b=A;I=I.next}for(l=1;l<B;l++){c=p+((P<l?P:l)<<2);v+=(I.r=k=u[c])*(M=B-l);m+=(I.g=L=u[c+1])*M;g+=(I.b=A=u[c+2])*M;x+=k;T+=L;N+=A;I=I.next}R=F;U=q;for(a=0;a<this.w;a++){u[p]=v*z>>W;u[p+1]=m*z>>W;u[p+2]=g*z>>W;v-=b;m-=w;g-=E;b-=R.r;w-=R.g;E-=R.b;c=d+((c=a+this.radius+1)<P?c:P)<<2;x+=R.r=u[c];T+=R.g=u[c+1];N+=R.b=u[c+2];v+=x;m+=T;g+=N;R=R.next;b+=k=U.r;w+=L=U.g;E+=A=U.b;x-=k;T-=L;N-=A;U=U.next;p+=4}d+=this.w}for(a=0;a<this.w;a++){T=N=x=m=g=v=0;p=a<<2;b=B*(k=u[p]);w=B*(L=u[p+1]);E=B*(A=u[p+2]);v+=j*k;m+=j*L;g+=j*A;I=F;for(l=0;l<B;l++){I.r=k;I.g=L;I.b=A;I=I.next}h=this.w;for(l=1;l<=this.radius;l++){p=h+a<<2;v+=(I.r=k=u[p])*(M=B-l);m+=(I.g=L=u[p+1])*M;g+=(I.b=A=u[p+2])*M;x+=k;T+=L;N+=A;I=I.next;l<H&&(h+=this.w)}p=a;R=F;U=q;for(f=0;f<this.h;f++){c=p<<2;u[c]=v*z>>W;u[c+1]=m*z>>W;u[c+2]=g*z>>W;v-=b;m-=w;g-=E;b-=R.r;w-=R.g;E-=R.b;c=a+((c=f+B)<H?c:H)*this.w<<2;v+=x+=R.r=u[c];m+=T+=R.g=u[c+1];g+=N+=R.b=u[c+2];R=R.next;b+=k=U.r;w+=L=U.g;E+=A=U.b;x-=k;T-=L;N-=A;U=U.next;p+=this.w}}}else{for(f=0;f<this.h;f++){x=T=N=C=v=m=g=y=0;b=B*(k=u[p]);w=B*(L=u[p+1]);E=B*(A=u[p+2]);S=B*(O=u[p+3]);v+=j*k;m+=j*L;g+=j*A;y+=j*O;I=F;for(l=0;l<B;l++){I.r=k;I.g=L;I.b=A;I.a=O;I=I.next}for(l=1;l<B;l++){c=p+((P<l?P:l)<<2);v+=(I.r=k=u[c])*(M=B-l);m+=(I.g=L=u[c+1])*M;g+=(I.b=A=u[c+2])*M;y+=(I.a=O=u[c+3])*M;x+=k;T+=L;N+=A;C+=O;I=I.next}R=F;U=q;for(a=0;a<this.w;a++){u[p+3]=O=y*z>>W;if(O!=0){O=255/O;u[p]=(v*z>>W)*O;u[p+1]=(m*z>>W)*O;u[p+2]=(g*z>>W)*O}else u[p]=u[p+1]=u[p+2]=0;v-=b;m-=w;g-=E;y-=S;b-=R.r;w-=R.g;E-=R.b;S-=R.a;c=d+((c=a+this.radius+1)<P?c:P)<<2;x+=R.r=u[c];T+=R.g=u[c+1];N+=R.b=u[c+2];C+=R.a=u[c+3];v+=x;m+=T;g+=N;y+=C;R=R.next;b+=k=U.r;w+=L=U.g;E+=A=U.b;S+=O=U.a;x-=k;T-=L;N-=A;C-=O;U=U.next;p+=4}d+=this.w}for(a=0;a<this.w;a++){T=N=C=x=m=g=y=v=0;p=a<<2;b=B*(k=u[p]);w=B*(L=u[p+1]);E=B*(A=u[p+2]);S=B*(O=u[p+3]);v+=j*k;m+=j*L;g+=j*A;y+=j*O;I=F;for(l=0;l<B;l++){I.r=k;I.g=L;I.b=A;I.a=O;I=I.next}h=this.w;for(l=1;l<=this.radius;l++){p=h+a<<2;v+=(I.r=k=u[p])*(M=B-l);m+=(I.g=L=u[p+1])*M;g+=(I.b=A=u[p+2])*M;y+=(I.a=O=u[p+3])*M;x+=k;T+=L;N+=A;C+=O;I=I.next;l<H&&(h+=this.w)}p=a;R=F;U=q;for(f=0;f<this.h;f++){c=p<<2;u[c+3]=O=y*z>>W;if(O>0){O=255/O;u[c]=(v*z>>W)*O;u[c+1]=(m*z>>W)*O;u[c+2]=(g*z>>W)*O}else u[c]=u[c+1]=u[c+2]=0;v-=b;m-=w;g-=E;y-=S;b-=R.r;w-=R.g;E-=R.b;S-=R.a;c=a+((c=f+B)<H?c:H)*this.w<<2;v+=x+=R.r=u[c];m+=T+=R.g=u[c+1];g+=N+=R.b=u[c+2];y+=C+=R.a=u[c+3];R=R.next;b+=k=U.r;w+=L=U.g;E+=A=U.b;S+=O=U.a;x-=k;T-=L;N-=A;C-=O;U=U.next;p+=this.w}}}i.putImageData(s,0,0);return!0};
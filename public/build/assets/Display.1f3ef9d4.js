import{f as w,o as s,c as a,a as e,t as h,g as p,h as k,j as i,b as f,w as v,T as y,F as x,i as S,e as j}from"./app.b834e386.js";import{D as G}from"./DisplayLayout.7ff56f32.js";import"./_plugin-vue_export-helper.cdc0426e.js";const I={class:"flex h-fit w-[30rem] items-center justify-center border-[16px] border-white bg-[url('/images/random-generator/background/comic-blue.jpg')] bg-cover bg-center"},B=["src"],C={key:1,class:"flex h-[30rem] transform flex-col items-center justify-center text-center font-eighty-miles text-8xl text-white"},R=w({__name:"AvatarCard",props:{src:{type:String,required:!1},firstname:{type:String,required:!0},lastname:{type:String,required:!0}},setup(c){const o=c;return(l,t)=>(s(),a("div",I,[o.src?(s(),a("img",{key:0,src:o.src,class:"h-fit"},null,8,B)):(s(),a("div",C,[e("span",null,h(o.firstname),1),e("span",null,h(o.lastname),1)]))]))}}),D={key:0,class:"flex h-screen"},T=e("div",{class:"m-auto rounded-full bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text font-eighty-miles text-[15rem] text-transparent"}," ZuFHallsgenerator ",-1),q=[T],F={key:1,class:"flex h-screen w-screen flex-col"},U={key:0,class:"flex h-screen w-screen flex-col overflow-hidden"},$={class:"h-screen flex-1 overflow-hidden"},A={class:"grid h-fit w-screen animate-fly justify-items-center space-y-32 overflow-hidden py-20"},E=e("img",{class:"absolute left-[10%] top-1/2 h-1/3 -translate-y-1/2 transform",src:"/images/random-generator/gifs/cat.gif"},null,-1),N=e("img",{class:"absolute right-[10%] top-1/2 h-1/3 -translate-y-1/2 scale-x-[-1] transform",src:"/images/random-generator/gifs/cat.gif"},null,-1),V={key:0,class:"flex h-screen items-center justify-center"},L=e("img",{class:"absolute left-0 top-1/2 h-2/3 -translate-y-1/2 transform",src:"/images/random-generator/gifs/trumpet.gif"},null,-1),H=e("img",{class:"absolute right-0 top-1/2 h-2/3 -translate-y-1/2 scale-x-[-1] transform",src:"/images/random-generator/gifs/trumpet.gif"},null,-1),M={key:2,autoplay:"",id:"running-sound"},O=e("source",{src:"/sounds/random-generator/running.mp3",type:"audio/mpeg"},null,-1),Z=[O],z=e("audio",{autoplay:""},[e("source",{src:"/sounds/random-generator/airhorn.mp3",type:"audio/mpeg"})],-1),J=e("audio",{autoplay:""},[e("source",{src:"/sounds/random-generator/rise.mp3",type:"audio/mpeg"})],-1),K={layout:G},Y=w({...K,__name:"Display",props:{users:{type:Array,required:!0}},setup(c){const o=c,l=p("stopped"),t=p({state:"setup"}),d=p(!1),b=setInterval(async()=>{if(d.value)return;d.value=!0;const m=await fetch("/api/random-generator/state",{method:"GET",credentials:"include",headers:{"Content-Type":"application/json"}});if(m.ok){const u=await m.json();if(t.value.state!="running"&&u.state=="running")l.value="running",o.users.sort(()=>Math.random()-.5);else if(t.value.state=="running"&&u.state=="stopped"){const r=document.getElementById("running-sound");if(r){const n=setInterval(()=>{r.volume>0?r.volume-=.1:l.value="stopped"},200);setTimeout(()=>{clearInterval(n),l.value="stopped",r.volume=1},2e3)}}t.value=u}d.value=!1},500);return k(()=>{clearInterval(b)}),(m,u)=>{const r=R;return s(),a("div",null,[t.value.state==="setup"?(s(),a("div",D,q)):t.value.state==="idle"?(s(),a("div",F)):i("",!0),f(y,null,{default:v(()=>[t.value.state==="running"?(s(),a("div",U,[e("div",$,[e("div",A,[(s(!0),a(x,null,S(c.users,n=>(s(),j(r,{class:"animate-wiggle",src:n.avatarUrl,firstname:n.firstname,lastname:n.lastname},null,8,["src","firstname","lastname"]))),256))]),E,N])])):i("",!0)]),_:1}),f(y,{name:"winner"},{default:v(()=>{var n,g,_;return[t.value.state==="stopped"?(s(),a("div",V,[f(r,{class:"scale-[130%]",src:(n=t.value.user)==null?void 0:n.avatarUrl,firstname:(g=t.value.user)==null?void 0:g.firstname,lastname:(_=t.value.user)==null?void 0:_.lastname},null,8,["src","firstname","lastname"]),L,H])):i("",!0)]}),_:1}),l.value!="stopped"?(s(),a("audio",M,Z)):i("",!0),t.value.state==="stopped"?(s(),a(x,{key:3},[z,J],64)):i("",!0)])}}});export{Y as default};

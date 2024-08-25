import{_ as F}from"./DashboardContent.40bbe9b8.js";import{_ as E}from"./Container.b310b897.js";import{f as A,m as S,g as q,h as T,e as s,w as i,o as e,d as g,t as v,b as a,c as u,F as l,j as r,a as B,R as G}from"./app.b834e386.js";import{_ as U}from"./Base.a4aae221.js";import{_ as j,a as R,b as z}from"./Dt.64ce192b.js";import{_ as I}from"./DateTimeString.vue_vue_type_script_setup_true_lang.5419d956.js";import{_ as M}from"./DashboardCardLayout.vue_vue_type_script_setup_true_lang.f77f1bd3.js";import"./_plugin-vue_export-helper.cdc0426e.js";import"./H1.bd5ff1ae.js";const N=["innerHTML"],V={layout:M},X=A({...V,__name:"Index",props:{event:{type:Object,required:!0},registration:{type:Object,required:!0}},setup(t){const{event:y,registration:c}=t,o=S(()=>y.slots&&c&&c.slot_id?y.slots.find(_=>_.id===c.slot_id):null),n=q(c),p=q(!1),b=setInterval(async()=>{if(p.value)return;p.value=!0;const _=await fetch("/api/registrations/"+c.id,{method:"GET",credentials:"include",headers:{"Content-Type":"application/json"}});if(_.ok){const k=await _.json();n.value=k}p.value=!1},5e3);return T(()=>{clearInterval(b)}),(_,k)=>{const d=j,f=R,h=I,x=z,D=U,m=G,w=E,C=F;return e(),s(C,null,{title:i(()=>[g(v(t.event.name),1)]),default:i(()=>[a(w,null,{default:i(()=>[a(D,null,{default:i(()=>[a(x,null,{default:i(()=>[a(d,null,{default:i(()=>[g("Event")]),_:1}),a(f,null,{default:i(()=>[g(v(t.event.name),1)]),_:1}),t.event.registration_from?(e(),u(l,{key:0},[a(d,null,{default:i(()=>[g("Anmeldung ab")]),_:1}),a(f,null,{default:i(()=>[a(h,{value:t.event.registration_from,withClockSuffix:!0},null,8,["value"])]),_:1})],64)):r("",!0),t.event.registration_to?(e(),u(l,{key:1},[a(d,null,{default:i(()=>[g("Anmeldung bis")]),_:1}),a(f,null,{default:i(()=>[a(h,{value:t.event.registration_to,withClockSuffix:!0},null,8,["value"])]),_:1})],64)):r("",!0),o.value?(e(),u(l,{key:2},[a(d,null,{default:i(()=>[g("Slot")]),_:1}),a(f,null,{default:i(()=>[g(v(o.value.name),1)]),_:1}),o.value.maximum_participants?(e(),u(l,{key:0},[a(d,null,{default:i(()=>[g("Maximale Teilnehmerzahl")]),_:1}),a(f,null,{default:i(()=>[g(v(o.value.maximum_participants),1)]),_:1})],64)):r("",!0)],64)):r("",!0)]),_:1})]),_:1}),n.value?(e(),u(l,{key:0},[t.event.type=="group_phase"?(e(),u(l,{key:0},[n.value.group_id&&n.value.group?(e(),u(l,{key:0},[!t.event.has_requirements||t.event.has_requirements&&n.value.fulfils_requirements?(e(),s(m,{key:0,type:"success",message:"Die Einteilung ist erfolgt. Du bist in "+(n.value.group.name?"der Gruppe <strong>"+n.value.group.name+"</strong>":"<strong>Gruppe "+n.value.group.id+"</strong>")+"."},null,8,["message"])):(e(),s(m,{key:1,type:"warning",message:"Die Einteilung ist erfolgt. Du bist f\xFCr "+(n.value.group.name?"der Gruppe <strong>"+n.value.group.name+"</strong>":"<strong>Gruppe "+n.value.group.id+"</strong>")+" <strong>vorgemerkt</strong>. Folge den mitgeteilten Anweisungen, um die Anmeldung abzuschlie\xDFen."},null,8,["message"]))],64)):(e(),s(m,{key:1,message:"Die Zuteilung in deine Gruppe folgt bald."}))],64)):t.event.type=="slot_booking"?(e(),u(l,{key:1},[n.value.queue_position==-1?(e(),s(m,{key:0,message:"Die Zuteilung in deinen Slot folgt bald."})):!n.value.queue_position&&o.value?(e(),u(l,{key:1},[!t.event.has_requirements&&!o.value.has_requirements||(t.event.has_requirements||o.value.has_requirements)&&n.value.fulfils_requirements?(e(),s(m,{key:0,type:"success",message:"Die Einteilung ist erfolgt. Du bist im Slot <strong>"+o.value.name+"</strong>."},null,8,["message"])):(e(),s(m,{key:1,type:"warning",message:"Die Einteilung ist erfolgt. Du bist f\xFCr den Slot <strong>"+o.value.name+" vorgemerkt</strong>. Folge den mitgeteilten Anweisungen, um die Anmeldung abzuschlie\xDFen."},null,8,["message"]))],64)):r("",!0)],64)):t.event.type=="event_registration"?(e(),u(l,{key:2},[t.event.has_requirements&&n.value.fulfils_requirements||!t.event.has_requirements?(e(),s(m,{key:0,type:"success",message:"Die Einteilung ist erfolgt. Du nimmst erfolgreich teil."})):t.event.has_requirements&&!n.value.fulfils_requirements?(e(),s(m,{key:1,type:"warning",message:"Die Einteilung ist erfolgt. Du bist f\xFCr das Event vorgemerkt. Folge den mitgeteilten Anweisungen, um die Anmeldung abzuschlie\xDFen."})):r("",!0)],64)):r("",!0),n.value.queue_position&&n.value.queue_position>0?(e(),s(m,{key:3,type:"warning",message:"Die Einteilung ist erfolgt. Du bist in der Warteschlange an <strong>Position "+n.value.queue_position+"</strong> als nachr\xFCckende Person eingetragen."},null,8,["message"])):r("",!0)],64)):r("",!0),t.event.description?(e(),s(D,{key:1},{default:i(()=>[B("div",{class:"prose !max-w-full dark:prose-invert",innerHTML:t.event.description},null,8,N)]),_:1})):r("",!0)]),_:1})]),_:1})}}});export{X as default};

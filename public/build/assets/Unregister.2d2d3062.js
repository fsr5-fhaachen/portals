import{_ as V,a as x}from"./DashboardContent.40bbe9b8.js";import{_ as D}from"./Base.a4aae221.js";import{_ as F,a as w}from"./Row.0a450704.js";import{_ as S,a as $,b as h}from"./Dt.64ce192b.js";import{_ as A}from"./DateTimeString.vue_vue_type_script_setup_true_lang.5419d956.js";import{f as B,g as E,e as N,w as n,p as j,o as s,d as o,b as e,t as H,c,F as d,j as f,k as K}from"./app.b834e386.js";import{_ as T}from"./DashboardCardLayout.vue_vue_type_script_setup_true_lang.f77f1bd3.js";import"./_plugin-vue_export-helper.cdc0426e.js";import"./H1.bd5ff1ae.js";const q={layout:T},W=B({...q,__name:"Unregister",props:{event:{type:Object,required:!0}},setup(t){const{event:p}=t,a=E({}),g=async()=>{K.Inertia.post("/dashboard/event/"+p.id+"/unregister",a.value)};return(z,m)=>{const v=x,l=F,u=S,_=$,r=A,b=h,i=j("FormKit"),y=w,C=D,k=V;return s(),N(k,null,{title:n(()=>[o("Abmeldung zur Veranstaltung")]),default:n(()=>[e(C,null,{default:n(()=>[e(i,{type:"form",id:"event-unregister",onSubmit:g,actions:!1,modelValue:a.value,"onUpdate:modelValue":m[0]||(m[0]=U=>a.value=U)},{default:n(()=>[e(y,null,{default:n(()=>[e(l,null,{default:n(()=>[e(v,null,{default:n(()=>[o("Eventdaten")]),_:1})]),_:1}),e(l,null,{default:n(()=>[e(b,null,{default:n(()=>[e(u,null,{default:n(()=>[o("Event")]),_:1}),e(_,null,{default:n(()=>[o(H(t.event.name),1)]),_:1}),t.event.registration_from?(s(),c(d,{key:0},[e(u,null,{default:n(()=>[o("Anmeldung ab")]),_:1}),e(_,null,{default:n(()=>[e(r,{value:t.event.registration_from,withClockSuffix:!0},null,8,["value"])]),_:1})],64)):f("",!0),t.event.registration_to?(s(),c(d,{key:1},[e(u,null,{default:n(()=>[o("Anmeldung bis")]),_:1}),e(_,null,{default:n(()=>[e(r,{value:t.event.registration_to,withClockSuffix:!0},null,8,["value"])]),_:1})],64)):f("",!0)]),_:1})]),_:1}),e(l,null,{default:n(()=>[e(i,{type:"submit",label:"Abmeldung"})]),_:1})]),_:1})]),_:1},8,["modelValue"])]),_:1})]),_:1})}}});export{W as default};

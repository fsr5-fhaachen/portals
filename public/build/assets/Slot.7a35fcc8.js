import{_ as L,a as b}from"./DashboardContent.40bbe9b8.js";import{_ as V}from"./Container.b310b897.js";import{_ as j}from"./Table.vue_vue_type_script_setup_true_lang.c722fc27.js";import{_ as x}from"./Base.a4aae221.js";import{_ as F,a as R}from"./Row.0a450704.js";import{f as k,g as _,m as B,h as S,e as p,w as e,p as T,o as d,d as f,t as I,b as t,j as N}from"./app.b834e386.js";import"./_plugin-vue_export-helper.cdc0426e.js";import"./H1.bd5ff1ae.js";import"./Button.vue_vue_type_script_setup_true_lang.865fb264.js";const J=k({__name:"Slot",props:{courses:{type:Array,required:!0},slot:{type:Object,required:!0},user:{type:Object,required:!0}},setup(s){const a=_({query:""}),{slot:i}=s,u=_(i.registrations),c=B(()=>a.value.query?u.value?u.value.filter(o=>{var n,l,r;return((n=o.user)==null?void 0:n.firstname.toLowerCase().includes(a.value.query.toLowerCase()))||((l=o.user)==null?void 0:l.lastname.toLowerCase().includes(a.value.query.toLowerCase()))||((r=o.user)==null?void 0:r.email.toLowerCase().includes(a.value.query.toLowerCase()))}):[]:u.value),v=setInterval(async()=>{const o=await fetch("/api/events/"+i.event_id+"/registrations",{method:"GET",credentials:"include",headers:{"Content-Type":"application/json"}});if(o.ok){const n=await o.json();u.value=n.slots[i.id]}},5e3);return S(()=>{clearInterval(v)}),(o,n)=>{const l=b,r=F,m=T("FormKit"),g=R,y=x,C=j,w=V,h=L;return d(),p(h,null,{title:e(()=>[f(I(s.slot.name),1)]),default:e(()=>[t(w,null,{default:e(()=>[t(y,null,{default:e(()=>[t(m,{type:"form",id:"assign",actions:!1,modelValue:a.value,"onUpdate:modelValue":n[0]||(n[0]=q=>a.value=q)},{default:e(()=>[t(g,null,{default:e(()=>[t(r,null,{default:e(()=>[t(l,null,{default:e(()=>[f("Filter")]),_:1})]),_:1}),t(r,null,{default:e(()=>[t(m,{type:"text",name:"query",label:"Suche"})]),_:1})]),_:1})]),_:1},8,["modelValue"])]),_:1}),s.slot.event&&c.value?(d(),p(C,{key:0,courses:s.courses,event:s.slot.event,registrations:c.value,hideSlots:!0,user:s.user},null,8,["courses","event","registrations","user"])):N("",!0)]),_:1})]),_:1})}}});export{J as default};

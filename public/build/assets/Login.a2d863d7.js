import{f as c,g as u,c as p,b as e,w as o,a as d,k as f,p as h,_ as g,o as v,u as b,d as y}from"./app.b834e386.js";import{_ as x,a as V}from"./Row.0a450704.js";import{_ as k}from"./CardLayout.vue_vue_type_script_setup_true_lang.9b0968bc.js";import{u as C}from"./usePlaceholderPerson.c4d28804.js";import"./_plugin-vue_export-helper.cdc0426e.js";const F={class:"relative mt-6 flex justify-center text-sm"},P={layout:k},K=c({...P,__name:"Login",props:{message:{type:Object,default:()=>({})}},setup(w){const t=u({}),s=C(),r=async()=>{f.Inertia.post("/login",t.value)};return(A,a)=>{const n=h("FormKit"),l=x,i=V,m=g;return v(),p("div",null,[e(n,{type:"form",id:"login",onSubmit:r,actions:!1,modelValue:t.value,"onUpdate:modelValue":a[0]||(a[0]=_=>t.value=_)},{default:o(()=>[e(i,null,{default:o(()=>[e(l,null,{default:o(()=>[e(n,{type:"email",name:"email",label:"E-Mail",placeholder:b(s).email,validation:"required|email"},null,8,["placeholder"])]),_:1}),e(l,null,{default:o(()=>[e(n,{type:"submit",label:"Anmelden"})]),_:1})]),_:1})]),_:1},8,["modelValue"]),d("div",F,[e(m,{href:"/register"},{default:o(()=>[y(" Du hast noch keinen Account? Dann registriere dich hier. ")]),_:1})])])}}});export{K as default};

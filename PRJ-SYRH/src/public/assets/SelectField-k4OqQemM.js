import{a as e,s as t,t as n}from"./jsx-runtime-Csv52NRi.js";import{t as r}from"./check-CDb7ubbk.js";import{t as i}from"./chevron-down-CvwaYJLj.js";var a=t(e(),1),o=n();function s({value:e,onChange:t,options:n,placeholder:s,className:c=``,selectClassName:l=``,size:u=`sm`,variant:d=`default`,disabled:f=!1}){let[p,m]=(0,a.useState)(!1),[h,g]=(0,a.useState)(!1),_=(0,a.useRef)(null),v=(0,a.useRef)(null);(0,a.useEffect)(()=>{if(!p)return;let e=e=>{_.current&&!_.current.contains(e.target)&&m(!1)};return document.addEventListener(`mousedown`,e),()=>document.removeEventListener(`mousedown`,e)},[p]),(0,a.useEffect)(()=>{if(!p)return;let e=e=>{e.key===`Escape`&&m(!1)};return document.addEventListener(`keydown`,e),()=>document.removeEventListener(`keydown`,e)},[p]),(0,a.useEffect)(()=>{if(!p||!_.current)return;let e=_.current.getBoundingClientRect();g(window.innerHeight-e.bottom<240)},[p]);let y=n.find(t=>t.value===e),b=y?.label||s||``,x=u===`sm`?`px-3 py-2 text-sm`:`px-4 py-2.5 text-base`,S=d===`glass`,C=S?`bg-white/10 border-white/20 text-white backdrop-blur-sm`:`border-beige-dark bg-white text-stone-700`;return(0,o.jsxs)(`div`,{className:`relative ${c}`,ref:_,children:[(0,o.jsxs)(`button`,{type:`button`,onClick:()=>{f||m(!p)},className:`
          w-full flex items-center justify-between gap-2 rounded-xl border
          font-medium cursor-pointer text-right rtl:text-right
          transition-all duration-200
          focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary
          hover:border-primary/30
          ${x}
          ${f?`opacity-50 cursor-not-allowed`:``}
          ${y?S?`text-white`:`text-stone-800`:S?`text-white/60`:`text-stone-400`}
          ${l||C}
          ${p?`border-primary ring-2 ring-primary/20`:``}
        `,children:[(0,o.jsx)(`span`,{className:`truncate flex-1`,children:b}),(0,o.jsx)(i,{className:`w-4 h-4 shrink-0 transition-transform duration-200 ${p?`rotate-180`:``} ${S?`text-white/50`:`text-stone-400`}`})]}),p&&(0,o.jsxs)(`div`,{ref:v,className:`
            absolute z-50 w-full min-w-[160px] rounded-xl border shadow-xl overflow-hidden
            ${h?`bottom-full mb-1.5`:`mt-1.5`}
            ${S?`bg-stone-800/95 backdrop-blur-xl border-white/10`:`bg-white border-beige-dark`}
          `,style:{animation:`${h?`dropdownUpIn`:`dropdownIn`} 0.15s ease-out`},children:[(0,o.jsx)(`style`,{children:`
            @keyframes dropdownIn{from{opacity:0;transform:translateY(-4px) scale(0.97)}to{opacity:1;transform:translateY(0) scale(1)}}
            @keyframes dropdownUpIn{from{opacity:0;transform:translateY(4px) scale(0.97)}to{opacity:1;transform:translateY(0) scale(1)}}
          `}),(0,o.jsxs)(`div`,{className:`max-h-56 overflow-y-auto py-1`,children:[s&&(0,o.jsx)(`button`,{type:`button`,onClick:()=>{t(``),m(!1)},className:`
                  w-full text-right rtl:text-right px-4 py-2.5 text-sm font-medium
                  transition-colors duration-100
                  ${e?S?`text-stone-400 hover:bg-white/10 hover:text-white`:`text-stone-500 hover:bg-beige hover:text-stone-700`:S?`bg-primary/20 text-gold-light`:`bg-primary/10 text-primary`}
                `,children:s}),n.map(n=>{let i=n.value===e;return(0,o.jsxs)(`button`,{type:`button`,onClick:()=>{t(n.value),m(!1)},className:`
                    w-full flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-right rtl:text-right
                    transition-colors duration-100
                    ${i?S?`bg-primary/20 text-gold-light font-semibold`:`bg-primary/10 text-primary font-semibold`:S?`text-stone-300 hover:bg-white/10 hover:text-white`:`text-stone-700 hover:bg-beige hover:text-stone-900`}
                  `,children:[i&&(0,o.jsx)(r,{className:`w-4 h-4 shrink-0 ${S?`text-gold-light`:`text-primary`}`}),(0,o.jsx)(`span`,{className:`flex-1`,children:n.label})]},n.value)})]})]})]})}export{s as t};
/* Partículas ciano leves, compartilhadas com a identidade visual da home. */
(function(){
 const canvas=document.getElementById('vbParticles');
 if(!canvas||window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
 const ctx=canvas.getContext('2d');if(!ctx)return;
 let w=0,h=0,dpr=1,particles=[],raf=0;
 const pointer={x:-1000,y:-1000};
 const amount=()=>Math.min(78,Math.max(30,Math.floor((w*h)/23000)));
 function resize(){
  dpr=Math.min(window.devicePixelRatio||1,2);w=window.innerWidth;h=window.innerHeight;
  canvas.width=Math.floor(w*dpr);canvas.height=Math.floor(h*dpr);
  canvas.style.width=w+'px';canvas.style.height=h+'px';ctx.setTransform(dpr,0,0,dpr,0,0);
  particles=Array.from({length:amount()},()=>({x:Math.random()*w,y:Math.random()*h,r:.7+Math.random()*1.6,vx:(Math.random()-.5)*.28,vy:-.1-Math.random()*.3,a:.18+Math.random()*.48}));
 }
 function draw(){
  ctx.clearRect(0,0,w,h);
  for(let i=0;i<particles.length;i++){
   const p=particles[i];p.x+=p.vx;p.y+=p.vy;
   if(p.y<-8){p.y=h+8;p.x=Math.random()*w}if(p.x<-8)p.x=w+8;if(p.x>w+8)p.x=-8;
   const dx=p.x-pointer.x,dy=p.y-pointer.y,dist=Math.hypot(dx,dy);
   if(dist<105&&dist>0){p.x+=dx/dist*.18;p.y+=dy/dist*.18}
   ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
   ctx.fillStyle='rgba(65,220,255,'+p.a+')';ctx.shadowBlur=9;ctx.shadowColor='rgba(39,215,255,.65)';ctx.fill();ctx.shadowBlur=0;
   for(let j=i+1;j<particles.length;j++){
    const q=particles[j],dd=Math.hypot(p.x-q.x,p.y-q.y);
    if(dd<88){ctx.beginPath();ctx.moveTo(p.x,p.y);ctx.lineTo(q.x,q.y);ctx.strokeStyle='rgba(39,215,255,'+((1-dd/88)*.105)+')';ctx.lineWidth=.6;ctx.stroke()}
   }
  }
  raf=requestAnimationFrame(draw);
 }
 window.addEventListener('resize',resize,{passive:true});
 window.addEventListener('pointermove',e=>{pointer.x=e.clientX;pointer.y=e.clientY},{passive:true});
 document.addEventListener('visibilitychange',()=>{cancelAnimationFrame(raf);if(!document.hidden)draw()});
 resize();draw();
})();
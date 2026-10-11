<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';

const canvasRef = ref<HTMLCanvasElement | null>(null);

onMounted(() => {
  const canvas = canvasRef.value;
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  let animationFrameId: number;
  const particleCount = 60;
  const connectionDistance = 150;
  const mouse = { x: -100, y: -100, radius: 150 };

  class Particle {
    x: number; y: number; vx: number; vy: number; size: number;
    constructor() {
      this.x = Math.random() * canvas!.width;
      this.y = Math.random() * canvas!.height;
      this.vx = (Math.random() - 0.5) * 0.5;
      this.vy = (Math.random() - 0.5) * 0.5;
      this.size = Math.random() * 2 + 1;
    }
    update() {
      this.x += this.vx;
      this.y += this.vy;
      const dx = mouse.x - this.x;
      const dy = mouse.y - this.y;
      const distance = Math.sqrt(dx * dx + dy * dy);
      if (distance < mouse.radius) {
        const force = (mouse.radius - distance) / mouse.radius;
        this.x -= (dx / distance) * force * 2;
        this.y -= (dy / distance) * force * 2;
      }
      if (this.x < 0 || this.x > canvas!.width) this.vx *= -1;
      if (this.y < 0 || this.y > canvas!.height) this.vy *= -1;
    }
    draw() {
      ctx!.beginPath();
      ctx!.arc(this.x, this.y, this.size, 0, Math.PI * 2);
      ctx!.fillStyle = 'rgba(6, 182, 212, 0.4)';
      ctx!.fill();
    }
  }

  let particles: Particle[] = [];

  const init = () => {
    canvas!.width = window.innerWidth;
    canvas!.height = window.innerHeight;
    particles = [];
    for (let i = 0; i < particleCount; i++) particles.push(new Particle());
  };

  const animate = () => {
    ctx.clearRect(0, 0, canvas!.width, canvas!.height);
    particles.forEach((p, i) => {
      p.update(); p.draw();
      for (let j = i + 1; j < particles.length; j++) {
        const p2 = particles[j];
        const dx = p.x - p2.x, dy = p.y - p2.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < connectionDistance) {
          ctx.beginPath();
          ctx.strokeStyle = `rgba(6, 182, 212, ${(1 - dist / connectionDistance) * 0.15})`;
          ctx.lineWidth = 1;
          ctx.moveTo(p.x, p.y);
          ctx.lineTo(p2.x, p2.y);
          ctx.stroke();
        }
      }
    });
    animationFrameId = requestAnimationFrame(animate);
  };

  const onMouseMove = (e: MouseEvent) => { mouse.x = e.clientX; mouse.y = e.clientY; };
  const onResize = () => init();

  window.addEventListener('mousemove', onMouseMove);
  window.addEventListener('resize', onResize);
  init();
  animate();

  onUnmounted(() => {
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('resize', onResize);
    cancelAnimationFrame(animationFrameId);
  });
});
</script>

<template>
  <canvas
    ref="canvasRef"
    class="fixed inset-0 z-[-1] pointer-events-none opacity-40"
    style="filter: blur(0.5px)"
  />
</template>

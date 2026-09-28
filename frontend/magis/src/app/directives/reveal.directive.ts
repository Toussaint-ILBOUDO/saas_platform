import { Directive, ElementRef, Input, OnDestroy, OnInit, Renderer2 } from '@angular/core';

type VarianteReveal = 'up' | 'down' | 'left' | 'right';

/**
 * Animation d'apparition au défilement (type AOS) : ajoute la classe
 * « reveal » puis « _visible » quand l'élément entre dans le viewport.
 * Usage : <div appReveal>… ou <div appReveal="left" delay="120">…
 */
@Directive({ selector: '[appReveal]' })
export class RevealDirective implements OnInit, OnDestroy {
  @Input('appReveal') variante: VarianteReveal | '' = 'up';
  @Input() delay = 0;

  private observer?: IntersectionObserver;

  constructor(
    private readonly el: ElementRef<HTMLElement>,
    private readonly renderer: Renderer2
  ) {}

  ngOnInit(): void {
    const element = this.el.nativeElement;
    this.renderer.addClass(element, 'reveal');
    this.renderer.setStyle(element, 'animation-delay', `${this.delay}ms`);
    if (this.variante !== 'up') {
      this.renderer.addClass(element, `reveal-${this.variante}`);
    }

    if (typeof IntersectionObserver === 'undefined') {
      this.renderer.addClass(element, '_visible');
      return;
    }

    this.observer = new IntersectionObserver(
      (entrees) => {
        for (const entree of entrees) {
          if (entree.isIntersecting) {
            this.renderer.addClass(element, '_visible');
            this.observer?.disconnect();
            break;
          }
        }
      },
      { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
    );
    this.observer.observe(element);
  }

  ngOnDestroy(): void {
    this.observer?.disconnect();
  }
}
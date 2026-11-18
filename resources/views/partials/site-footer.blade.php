<footer class="footer" aria-label="Информация о сервисе">
  <div class="footer-about">
    @if(request()->routeIs('landing'))
      <span class="footer-brand">@include('partials.brand')</span>
    @else
      <a class="footer-brand" href="{{ route('landing') }}" aria-label="IQ.Профиль — главная">@include('partials.brand')</a>
    @endif
    <p>Онлайн-задания на логику и поиск закономерностей.</p>
  </div>
  <details class="footer-group">
    <summary>Тесты</summary>
    <div class="footer-links">
      <a href="{{ route('landing') }}#tests">IQ-тест</a>
      <span>Логическое мышление — скоро</span>
      <span>Внимание к деталям — скоро</span>
    </div>
  </details>
  <details class="footer-group">
    <summary>О сервисе</summary>
    <div class="footer-links">
      <a href="{{ route('landing') }}#benefits">Преимущества</a>
      <a href="{{ route('landing') }}#steps">Как это работает</a>
      <a href="{{ route('landing') }}#faq">FAQ</a>
    </div>
  </details>
  <details class="footer-group">
    <summary>Информация</summary>
    <div class="footer-links">
      <a href="{{ route('legal.privacy') }}">Политика конфиденциальности</a>
      <a href="{{ route('legal.offer') }}">Публичная оферта (демо)</a>
      <a href="{{ route('legal.consent') }}">Согласие на обработку персональных данных</a>
      <a href="{{ route('subscription.cancel') }}">Отменить подписку</a>
    </div>
  </details>
  <p class="footer-copyright">© {{ date('Y') }} IQ.Профиль. Демо-оплата без реальных списаний.</p>
</footer>
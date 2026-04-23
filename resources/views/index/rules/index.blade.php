@extends('index.layouts.main')

@section('content')
    <div class="rules-page py-5">
        <div class="container">

            <section class="rules-hero mb-5">
                <div class="rules-hero-card">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-8">
                            <span class="rules-badge">Корпорація Мов</span>
                            <h1 class="rules-page-title mb-3">Правила школи</h1>
                            <p class="rules-page-text mb-0">
                                Нижче зібрані основні правила навчання, оплати та організації занять.
                                Радимо ознайомитися з ними перед початком навчання.
                            </p>
                        </div>

                        <div class="col-lg-4">
                            <div class="rules-hero-side">
                                <div class="rules-hero-side-title">Важливо</div>
                                <div class="rules-hero-side-text">
                                    Якщо у вас виникли питання щодо правил, зверніться до адміністратора школи.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rules-main">
                @if($rules->count())
                    <div class="rules-accordion" id="schoolRulesAccordion">
                        @foreach($rules as $rule)
                            <div class="rule-item">
                                <button
                                    class="rule-toggle collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#ruleCollapse{{ $rule->id }}"
                                    aria-expanded="false"
                                    aria-controls="ruleCollapse{{ $rule->id }}"
                                >
                                    <span class="rule-toggle-title">{{ $rule->title }}</span>
                                    <span class="rule-toggle-icon">+</span>
                                </button>

                                <div
                                    id="ruleCollapse{{ $rule->id }}"
                                    class="collapse"
                                    data-bs-parent="#schoolRulesAccordion"
                                >
                                    <div class="rule-content">
                                        {!! nl2br(e($rule->content)) !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="content-card">
                        <p class="mb-0">Правила школи ще не додані.</p>
                    </div>
                @endif
            </section>

        </div>
    </div>
@endsection

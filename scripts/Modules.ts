/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 모듈의 자바스크립트 클래스를 관리하는 클래스를 정의한다.
 *
 * @file /scripts/Modules.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
 */
interface ModuleConstructor {
    new (dom?: Dom): Module;
}

class Modules {
    static classes: { [key: string]: ModuleConstructor } = {};
    static domClasses: WeakMap<object, Module> = new WeakMap();

    /**
     * 페이지에 삽입되어 있는 모든 모듈의 Dom 객체를 초기화한다.
     */
    static init(): void {
        Html.all('*[data-role=module][data-module]').forEach((dom: Dom) => {
            Modules.dom(dom)?.init();
        });
    }

    /**
     * 모듈 클래스를 설정한다.
     *
     * @param {ModuleConstructor} module - 모듈 클래스 정의
     */
    static set(module: ModuleConstructor): void {
        Modules.classes[module.name] = module;
    }

    /**
     * 모듈 클래스를 가져온다.
     *
     * @param {string} name - 모듈명
     * @param {Dom} dom - 모듈의 DOM
     * @return {?Module} module - 모듈 클래스
     */
    static get(name: string, dom?: Dom): Module | null {
        if (Modules.classes[name] === undefined) {
            return null;
        }

        return new Modules.classes[name](dom);
    }

    /**
     * 모듈 DOM을 위한 모듈 클래스를 가져온다.
     *
     * @param {Dom} dom - 모듈 DOM
     */
    static dom(dom: Dom): Module | null {
        if (Modules.domClasses.has(dom.getEl()) == true) {
            return Modules.domClasses.get(dom.getEl());
        }

        Modules.domClasses.set(dom.getEl(), Modules.get(dom.getData('module'), dom));
        return Modules.domClasses.get(dom.getEl());
    }
}

/**
 * HTML 문서 랜더링이 완료되면 모듈 클래스를 초기화한다.
 */
Html.ready(Modules.init);

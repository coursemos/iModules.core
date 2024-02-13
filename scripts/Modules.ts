/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 모듈의 자바스크립트 클래스를 관리하는 클래스를 정의한다.
 *
 * @file /scripts/Modules.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 14.
 */
class Modules {
    static modules: Map<string, Module> = new Map();
    static classes: { [key: string]: Module } = {};

    /**
     * 모듈 클래스를 가져온다.
     *
     * @param {string} name - 모듈명
     * @return {Module} module - 모듈 클래스
     */
    static get(name: string): Module {
        if (Modules.modules.has(name) == false) {
            const namespaces = name.split('/');
            if (window['modules'] === undefined) {
                return null;
            }

            let namespace: Object | Modules.ModuleConstructor = window['modules'];
            for (const name of namespaces) {
                if (namespace[name] === undefined) {
                    console.warn('NOT_FOUND_NAMESPACE', namespace, name);
                    return null;
                }
                namespace = namespace[name];
            }
            const classname = namespaces.pop().replace(/^[a-z]/, (char: string) => char.toUpperCase());
            if (namespace[classname] === undefined) {
                return null;
            }

            if (typeof namespace[classname] == 'function' && namespace[classname].prototype instanceof Module) {
                Modules.modules.set(name, new (namespace[classname] as Modules.ModuleConstructor)(name));
                return Modules.modules.get(name);
            }

            return null;
        }

        return Modules.modules.get(name);
    }

    /**
     * 페이지에 삽입되어 있는 모든 모듈의 Dom 객체를 초기화한다.
     */
    static init(): void {
        Html.all('*[data-role=module][data-module]').forEach(($dom: Dom) => {
            Modules.get($dom.getAttr('data-module'))?.init($dom);
        });
    }
}

namespace Modules {
    export interface ModuleConstructor {
        new (name: string): Module;
    }
}

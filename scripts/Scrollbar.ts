/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 특정영역의 스크롤바를 정의한다.
 *
 * @file /scripts/Scrollbar.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 23.
 */
class Scrollbar {
    /**
     * 사용자 정의 스크롤바를 사용하는 DOM 을 초기화한다.
     */
    static init(): void {
        if (
            Html.get('body').getAttr('data-type') == 'website' &&
            Html.get('body').getAttr('data-device') == 'desktop'
        ) {
            Html.all('*[data-scrollbar]').forEach(($dom) => {
                Scrollbar.create($dom);
            });

            requestAnimationFrame(Scrollbar.rendering);
        }
    }

    /**
     * 사용자 정의 스크롤바를 사용하는 DOM 내부에 스크롤바를 생성한다.
     *
     * @param {Dom} $dom - 사용자 정의 스크롤바를 사용하는 DOM 객체
     */
    static create($dom: Dom): void {
        if ($dom.getData('scrollbar-init') !== true && Html.get('> div[data-role=scrollbar]', $dom).getEl() === null) {
            const $scrollbar = Html.create('div', { 'data-role': 'scrollbar' });
            $scrollbar.append(Scrollbar.createTrack('x'));
            $scrollbar.append(Scrollbar.createTrack('y'));
            $dom.append($scrollbar);
            $dom.setData('scrollbar-init', true, false);
        }
    }

    /**
     * 특정 방향의 스크롤바 트랙을 생성한다.
     *
     * @param {('x'|'y')} direction - 방향
     * @return {Dom} $scrollbar
     */
    static createTrack(direction: 'x' | 'y'): Dom {
        const $axis = Html.create('div', { 'data-role': 'axis', 'data-direction': direction });
        const $track = Html.create('div', { 'data-role': 'track' });
        const $thumb = Html.create('div', { 'data-role': 'thumb' });
        $track.append($thumb);
        $axis.append($track);

        return $axis;
    }

    /**
     * 특정 영역의 스크롤바를 랜더링한다.
     *
     * @param {Dom} $dom - 사용자 정의 스크롤바를 사용하는 DOM 객체
     */
    static render($dom: Dom): void {
        const scrollWidth = $dom.getScrollWidth();
        const scrollHeight = $dom.getScrollHeight();
        const innerWidth = $dom.is('body') == true ? window.innerWidth : $dom.getInnerWidth();
        const innerHeight = $dom.is('body') == true ? window.innerHeight : $dom.getInnerHeight();

        /**
         * X축
         */
        const $x = Html.get('> div[data-role=scrollbar] > div[data-direction=x]', $dom);
        if (scrollWidth <= innerWidth) {
            $x.hide();
        } else {
            $x.setStyle('width', innerWidth + 'px');
            $x.show();
            const $thumb = Html.get('div[data-role=thumb]', $x);
            const $track = Html.get('div[data-role=track]', $x);
            const thumbWidth = (innerWidth / scrollWidth) * innerWidth;
            const trackWidth = $track.getOuterWidth() - thumbWidth;
            const scrollLeft = $dom.is('body') == true ? document.documentElement.scrollLeft : $dom.getScroll().left;

            const thumbLeft = (scrollLeft / (scrollWidth - innerWidth)) * trackWidth;
            $thumb.setStyle('height', thumbWidth + 'px');
            $thumb.setStyle('top', thumbLeft + 'px');
        }

        /**
         * Y축
         */
        const $y = Html.get('> div[data-role=scrollbar] > div[data-direction=y]', $dom);
        if (scrollHeight <= innerHeight) {
            $y.hide();
        } else {
            $y.setStyle('height', innerHeight + 'px');
            $y.show();
            const $thumb = Html.get('div[data-role=thumb]', $y);
            const $track = Html.get('div[data-role=track]', $y);
            const thumbHeight = (innerHeight / scrollHeight) * innerHeight;
            const trackHeight = $track.getOuterHeight() - thumbHeight;
            const scrollTop = $dom.is('body') == true ? document.documentElement.scrollTop : $dom.getScroll().top;

            const thumbTop = (scrollTop / (scrollHeight - innerHeight)) * trackHeight;
            $thumb.setStyle('height', thumbHeight + 'px');
            $thumb.setStyle('top', thumbTop + 'px');
        }
    }

    /**
     * 스크롤바를 랜더링한다.
     */
    static rendering(): void {
        Html.all('*[data-scrollbar]').forEach(($dom) => {
            if ($dom.getData('scrollbar-init') !== true) {
                Scrollbar.create($dom);
            }
            Scrollbar.render($dom);
        });

        requestAnimationFrame(Scrollbar.rendering);
    }
}

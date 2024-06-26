/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 스크롤바를 정의한다.
 *
 * @file /scripts/Scroll.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 4. 30.
 */
class Scroll {
    static scrolls: WeakMap<HTMLElement, Scroll> = new WeakMap();

    $dom: Dom;
    scrollable: { x: boolean; y: boolean } = { x: false, y: false };
    $axis: { x: Dom; y: Dom } = { x: null, y: null };

    latestPosition: { x: number; y: number } = { x: 0, y: 0 };
    latestTargetSize: { width: number; height: number } = { width: 0, height: 0 };

    /**
     * 사용자 정의 스크롤바를 사용하는 DOM 을 초기화한다.
     */
    static init(): void {
        if (Html.get('body').getAttr('data-type') == 'website') {
            Html.all('*[data-scroll=true]').forEach(($dom) => {
                if ($dom.getAttr('data-device') == 'desktop' || $dom.is('body') == false) {
                    Scroll.create($dom);
                }
            });

            requestAnimationFrame(Scroll.rendering);
        }
    }

    /**
     * 스크롤영역을 생성한다.
     *
     * @param {Dom} $dom - 스크롤이 되는 객체
     * @param {boolean|'x'|'y'} scrollable - 스크롤방향 (NULL 인 경우 DOM 설정)
     */
    static create($dom: Dom, scrollable: boolean | 'x' | 'y' = null): void {
        if (scrollable === null) {
            const x = $dom.getAttr('data-scroll-x') == 'true';
            const y = $dom.getAttr('data-scroll-y') == 'true';

            if (x == true && y == true) {
                scrollable = true;
            } else if (x == true) {
                scrollable = 'x';
            } else if (y == true) {
                scrollable = 'y';
            } else {
                scrollable = false;
            }
        }

        Scroll.scrolls.set($dom.getEl(), new Scroll($dom, scrollable));
    }

    /**
     * 스크롤영역을 정의한다.
     *
     * @param {Dom} $dom - 스크롤이 되는 객체
     * @param {boolean|'x'|'y'} scrollable - 스크롤방향
     */
    constructor($dom: Dom, scrollable: boolean | 'x' | 'y') {
        this.$dom = $dom;

        if (scrollable === false) {
            this.scrollable.x = false;
            this.scrollable.y = false;
        } else {
            this.scrollable.x = scrollable === true || scrollable.toLowerCase() == 'x';
            this.scrollable.y = scrollable === true || scrollable.toLowerCase() == 'y';
        }

        this.render();
    }

    /**
     * 스크롤바축 DOM 을 가져온다.
     *
     * @return Dom $dom
     */
    $getAxis(direction: 'x' | 'y'): Dom {
        if (this.$axis[direction] === null) {
            this.$axis[direction] = Html.create('div', { 'data-role': 'axis', 'data-direction': direction });

            if (direction == 'x') {
                this.$axis[direction].setStyle('width', this.getTargetSize('x') + 'px');
            } else {
                this.$axis[direction].setStyle('height', this.getTargetSize('y') + 'px');
            }

            const $track = Html.create('div', { 'data-role': 'track' });
            const $thumb = Html.create('div', { 'data-role': 'thumb' });
            $track.append($thumb);

            this.$axis[direction].append($track);
        }

        return this.$axis[direction];
    }

    /**
     * 스크롤되는 영역의 크기를 가져온다.
     *
     * @param {'x'|'y'} direction - 스크롤 영역을 가져올 스크롤롤축
     * @return {number} offset
     */
    getTargetSize(direction: 'x' | 'y'): number {
        if (direction == 'x') {
            return this.$dom.is('body') == true ? window.innerWidth : this.$dom.getOuterWidth();
        } else {
            return this.$dom.is('body') == true ? window.innerHeight : this.$dom.getOuterHeight();
        }
    }

    /**
     * 스크롤 가능영역을 가져온다.
     *
     * @param {'x'|'y'} direction - 스크롤 영역을 가져올 스크롤롤축
     * @return {number} offset
     */
    getScrollOffset(direction: 'x' | 'y'): number {
        if (direction == 'x') {
            return this.$dom.getScrollWidth() - this.getTargetSize(direction);
        } else {
            return this.$dom.getScrollHeight() - this.getTargetSize(direction);
        }
    }

    /**
     * 현재 스크롤 위치를 가져온다.
     *
     * @return {Object} position - 스크롤위치 (x,y)
     */
    getPosition(): { x: number; y: number } {
        if (this.$dom.is('body') == true) {
            return { x: document.documentElement.scrollLeft, y: document.documentElement.scrollTop };
        } else {
            const scroll = this.$dom.getScroll();
            return { x: scroll.left, y: scroll.top };
        }
    }

    /**
     * 스크롤위치에 따른 트랙의 위치를 가져온다.
     *
     * @param {'x'|'y'} direction - 위치를 가져올 스크롤축
     * @return {number} position - 트랙위치
     */
    getScrollToTrackPosition(direction: 'x' | 'y'): number {
        const $track = Html.get('div[data-role=track]', this.$getAxis(direction));
        const $thumb = Html.get('div[data-role=thumb]', $track);

        if (this.getScrollOffset(direction) == 0) {
            return 0;
        }

        let trackLength = 0;
        if (direction == 'x') {
            trackLength = $track.getWidth() - $thumb.getOuterWidth();
        } else {
            trackLength = $track.getHeight() - $thumb.getOuterHeight();
        }

        if (trackLength == 0) {
            return 0;
        }

        return Math.round((this.getPosition()[direction] / this.getScrollOffset(direction)) * trackLength);
    }

    /**
     * 스크롤바 위치를 다시 조절한다.
     */
    updatePosition(): void {
        if (
            this.latestTargetSize.width === this.getTargetSize('x') &&
            this.latestTargetSize.height === this.getTargetSize('y')
        ) {
            return;
        }

        this.$getAxis('x').setStyle('width', this.getTargetSize('x') + 'px');
        this.$getAxis('y').setStyle('height', this.getTargetSize('y') + 'px');

        this.latestTargetSize.width = this.getTargetSize('x');
        this.latestTargetSize.height = this.getTargetSize('y');
    }

    /**
     * 스크롤이 가능한지 확인한다.
     *
     * @param {('x'|'y')} direction - 스크롤 가능여부를 확인할 스크롤축
     * @return {boolean} scrollable
     */
    isScrollable(direction: 'x' | 'y'): boolean {
        return this.scrollable[direction] && this.getScrollOffset(direction) > 0;
    }

    /**
     * 스크롤바를 위한 이벤트를 등록한다.
     */
    setEvent(): void {
        if (this.$dom.is('body') == true) {
            Html.scroll(() => {
                const current = this.getPosition();
                if (this.latestPosition.x !== current.x) {
                    this.active('x', 1);
                }

                if (this.latestPosition.y !== current.y) {
                    this.active('y', 1);
                }

                this.latestPosition = current;
            });
        } else {
            this.$dom.on('scroll', () => {
                const current = this.getPosition();
                if (this.latestPosition.x !== current.x) {
                    this.active('x', 1);
                }

                if (this.latestPosition.y !== current.y) {
                    this.active('y', 1);
                }

                this.latestPosition = current;
            });
        }

        this.setTrackEvent('x');
        this.setTrackEvent('y');
    }

    /**
     * 스크롤바 이벤트를 처리한다.
     *
     * @param {('x'|'y')} direction - 이벤트를 처리할 스크롤축
     */
    setTrackEvent(direction: 'x' | 'y'): void {
        this.$getAxis(direction).on('mouseover', this.active.bind(this, direction, 0));
        this.$getAxis(direction).on('mouseout', this.deactive.bind(this, direction, 1));
    }

    /**
     * 스크롤바를 활성화한다.
     *
     * @param {('x'|'y')} direction - 활성화할 스크롤바 축
     * @param {number} delay - 자동으로 비활성화할 딜레이시간(0 인 경우 자동으로 비활성화하지 않음)
     */
    active(direction: 'x' | 'y', delay: number = 0): void {
        const $axis = this.$getAxis(direction);
        if ($axis.getData('timer')) {
            clearTimeout($axis.getData('timer'));
        }

        $axis.addClass('active');

        if (delay > 0) {
            this.deactive(direction, delay);
        }
    }

    /**
     * 스크롤바를 비활성화한다.
     *
     * @param {('x'|'y')} direction - 비활성화할 스크롤바 축
     * @param {number} delay - 비활성화할 딜레이시간(0 인 경우 즉시 비활성화)
     */
    deactive(direction: 'x' | 'y', delay: number = 0): void {
        const $axis = this.$getAxis(direction);
        if ($axis.getData('timer')) {
            clearTimeout($axis.getData('timer'));
        }

        if ($axis.hasClass('drag') == true) {
            return;
        }

        if (delay > 0) {
            $axis.setData(
                'timer',
                setTimeout(
                    ($scrollbar: Dom) => {
                        $scrollbar.removeClass('active');
                    },
                    delay * 1000,
                    $axis
                ),
                false
            );
        } else {
            $axis.removeClass('active');
        }
    }

    /**
     * 스크롤바 트랙을 업데이트한다.
     *
     * @param {'x'|'y'} direction - 업데이트할 스크롤축
     */
    updateTrack(direction: 'x' | 'y'): void {
        const $axis = this.$getAxis(direction);

        if (this.isScrollable(direction) == true) {
            $axis.setData('disabled', 'false');

            const $track = Html.get('div[data-role=track]', $axis);
            const $thumb = Html.get('div[data-role=thumb]', $track);

            switch (direction) {
                case 'x':
                    $thumb.setStyle('width', Math.ceil(($track.getWidth() / this.$dom.getScrollWidth()) * 100) + '%');
                    $thumb.setStyle('left', this.getScrollToTrackPosition(direction) + 'px');
                    break;

                case 'y':
                    $thumb.setStyle(
                        'height',
                        Math.ceil(($track.getHeight() / this.$dom.getScrollHeight()) * 100) + '%'
                    );
                    $thumb.setStyle('top', this.getScrollToTrackPosition(direction) + 'px');
                    break;
            }
        } else if (this.$getAxis(direction).getData('disabled') != 'true') {
            $axis.setData('disabled', 'true');
        }
    }

    /**
     * 스크롤영역을 업데이트한다.
     */
    update(): void {
        this.updatePosition();
        this.updateTrack('x');
        this.updateTrack('y');
    }

    /**
     * 스크롤영역을 랜더링한다.
     */
    render(): void {
        if (this.$dom.getAttr('data-scroll') != 'true') {
            return;
        }

        if (Html.get('div[data-role=scrollbar]', this.$dom).getEl() === null) {
            const $scrollbar = Html.create('div', { 'data-role': 'scrollbar' });
            $scrollbar.append(this.$getAxis('x'));
            $scrollbar.append(this.$getAxis('y'));
            this.$dom.append($scrollbar);

            this.setEvent();

            this.updateTrack('x');
            this.updateTrack('y');
        }
    }

    /**
     * 스크롤영역을 지속적으로 랜더링한다.
     */
    static rendering(): void {
        Html.all('*[data-scroll=true]').forEach(($dom) => {
            const scroll = Scroll.scrolls.get($dom.getEl()) ?? null;
            scroll?.update();
        });

        requestAnimationFrame(Scroll.rendering);
    }
}

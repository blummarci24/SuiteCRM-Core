import {Injectable} from '@angular/core';
import {StateStore} from '@store/state';
import {BehaviorSubject, Observable, of} from 'rxjs';
import {deepClone} from '@base/app-common/utils/object-utils';
import {Statistic, StatisticsMap, StatisticsQuery} from '@app-common/statistics/statistics.model';
import {distinctUntilChanged, map, shareReplay, tap} from 'rxjs/operators';
import {StatisticsFetchGQL} from '@store/statistics/graphql/api.statistics.get';
import {ViewContext} from '@app-common/views/view.model';
import {StatisticsState} from '@app-common/statistics/statistics-store.model';

const initialState = {
    module: '',
    query: {} as StatisticsQuery,
    statistic: {
        id: '',
        data: {}
    } as Statistic,
    loading: false
} as StatisticsState;

@Injectable()
export class StatisticsStore implements StateStore {
    state$: Observable<StatisticsState>;
    statistic$: Observable<Statistic>;
    loading$: Observable<boolean>;
    protected cache$: Observable<any> = null;
    protected internalState: StatisticsState = deepClone(initialState);
    protected store = new BehaviorSubject<StatisticsState>(this.internalState);

    constructor(
        protected fetchGQL: StatisticsFetchGQL,
    ) {
        this.state$ = this.store.asObservable();
        this.statistic$ = this.state$.pipe(map(state => state.statistic), distinctUntilChanged());
        this.loading$ = this.state$.pipe(map(state => state.loading), distinctUntilChanged());
    }

    clear(): void {
        this.store.unsubscribe();
        this.cache$ = null;
    }

    clearAuthBased(): void {
        this.clear();
    }

    /**
     * Get Statistic query
     *
     * @returns {object} StatisticsQuery
     */
    public getQuery(): StatisticsQuery {
        return deepClone(this.internalState.query);
    }

    get context(): ViewContext {
        return this.internalState.query.context;
    }

    set context(context: ViewContext) {
        const query = deepClone(this.internalState.query);
        query.context = context;

        this.updateState({
            ...this.internalState,
            query
        });
    }

    /**
     * Initial list records load if not cached and update state.
     * Returns observable to be used in resolver if needed
     *
     * @param {string} module to use
     * @param {object} query to use
     * @param {boolean} load if to load
     * @returns {object} Observable<any>
     */
    public init(module: string, query: StatisticsQuery, load = true): Observable<Statistic> {
        this.internalState.module = module;
        this.updateState({
            ...this.internalState,
            module,
            query
        });

        if (load === false) {
            return null;
        }

        return this.load();
    }

    /**
     * Load / reload statistics
     *
     * @param {boolean} useCache if to use cache
     * @returns {object} Observable<ListViewState>
     */
    public load(useCache = true): Observable<Statistic> {
        this.updateState({
            ...this.internalState,
            loading: true,
        });

        return this.fetchStatistics(this.internalState.module, this.getQuery(), useCache).pipe(
            map((data: StatisticsMap) => this.mapStatistics(data)),
            tap((statistic: Statistic) => {
                this.addNewState(statistic);
            })
        );
    }

    /**
     * Set loading
     *
     * @param {boolean} loading bool
     */
    public setLoading(loading: boolean): void {

        this.updateState({
            ...this.internalState,
            loading
        });
    }

    /**
     * Set Statistic value
     *
     * @param {string} key string
     * @param {object} statistic Statistic
     * @param {boolean} cache bool
     */
    public setStatistic(key: string, statistic: Statistic, cache = false): void {

        this.addNewState(statistic);

        if (!cache) {
            return;
        }

        const statMap: StatisticsMap = {};
        statMap[key] = statistic;

        this.cache$ = of(statMap).pipe(shareReplay(1));
    }

    protected addNewState(statistic: Statistic): void {
        this.updateState({
            ...this.internalState,
            statistic,
            loading: false
        });
    }

    protected mapStatistics(data: StatisticsMap): Statistic {
        const keys = Object.keys(data);
        const key = keys && keys.length && keys[0];
        let statistic = {id: '', data: {}} as Statistic;
        if (key) {
            statistic = data[key];
        }
        return statistic;
    }

    /**
     * Update the state
     *
     * @param {object} state to set
     */
    protected updateState(state: StatisticsState): void {
        this.store.next(this.internalState = state);
    }

    /**
     * Get records cached Observable or call the backend
     *
     * @param {string} module to use
     * @param {object} query to use
     * @param {boolean} useCache if to use cache
     * @returns {object} Observable<any>
     */
    protected fetchStatistics(
        module: string,
        query: StatisticsQuery,
        useCache = true
    ): Observable<StatisticsMap> {

        const queries = {};
        queries[query.key] = query;

        if (this.cache$ == null || useCache === false) {
            this.cache$ = this.fetchGQL.fetch(module, queries).pipe(
                shareReplay(1)
            );
        }
        return this.cache$;
    }

}

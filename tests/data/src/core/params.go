package test

func a() {}

func b(x []int) int {
    return 1
}

func c(a, _ int, z float32) bool {
    return true
}

func d(a, b int, z float32) (bool) {
    return false
}

func e(prefix string, values ...int) {}

func f(a, b int, z List[int], opt ...interface{}) (success bool) {
    success = true
    return
}

func g(int, int, float64) (float64, *[]int) {
    var s *[]int
    return 1.1, s
}

func h[T any](n int) func(p *T) {
    var s func(p *T)
    return s
}

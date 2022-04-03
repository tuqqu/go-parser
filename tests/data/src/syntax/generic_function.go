package test

func MapKeys[K comparable, V any](m map[K]V) []K {
    r := make([]K, 0, len(m))
    return r
}

func acceptAnything[T any](thing T) {
}

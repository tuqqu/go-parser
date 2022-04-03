package test

type List[T any] struct {
	next  *List[T]
}

package test

type List[T any] struct {
	next  *List[T]
}

// typedef of slice
type a []int

// typedef of array with constant length
type b [x]int

// generic typedefs
type c[T any] int

type d[T []int] []int

type e[T any] [x]int

type f[T, U int | []int] []int
